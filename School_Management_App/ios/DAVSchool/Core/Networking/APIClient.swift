import Foundation
#if canImport(FoundationNetworking)
import FoundationNetworking
#endif

/// Envelope used by every endpoint: `{"data": ..., "message": "..."}`.
public struct Envelope<T: Decodable>: Decodable {
    public let data: T
    public let message: String?

    enum CodingKeys: String, CodingKey { case data, message }

    public init(from decoder: Decoder) throws {
        let c = try decoder.container(keyedBy: CodingKeys.self)
        if let opt = T.self as? OptionalMarker.Type, (!c.contains(.data) || ((try? c.decodeNil(forKey: .data)) ?? false)) {
            data = opt.nilValue as! T
        } else {
            data = try c.decode(T.self, forKey: .data)
        }
        message = try? c.decodeIfPresent(String.self, forKey: .message)
    }
}

protocol OptionalMarker { static var nilValue: Any { get } }
extension Optional: OptionalMarker { static var nilValue: Any { Optional<Wrapped>.none as Any } }

/// List envelope with paging meta: `{"data":[...], "meta":{...}}`.
public struct PagedEnvelope<T: Decodable>: Decodable {
    public let data: [T]
    public let meta: PageMeta?
    public let message: String?
}

public struct PageMeta: Decodable, Hashable {
    public let total: Int?
    public let page: Int?
    public let perPage: Int?
    public let lastPage: Int?
    public let unread: Int?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        total = r.int("total")
        page = r.int("page") ?? r.int("current_page")
        perPage = r.int("per_page")
        lastPage = r.int("last_page")
        unread = r.int("unread")
    }

    public init(total: Int?, page: Int?, perPage: Int?, lastPage: Int?, unread: Int? = nil) {
        self.total = total; self.page = page; self.perPage = perPage; self.lastPage = lastPage; self.unread = unread
    }
}

public struct MessageOnly: Decodable { public let message: String? }

/// A file downloaded from the server (PDF, Excel, backup zip).
public struct DownloadedFile {
    public let url: URL
    public let mimeType: String?
    public let suggestedName: String
}

/// One part of a multipart upload.
public struct UploadFile {
    public let field: String
    public let fileName: String
    public let mimeType: String
    public let data: Data
    public init(field: String, fileName: String, mimeType: String, data: Data) {
        self.field = field; self.fileName = fileName; self.mimeType = mimeType; self.data = data
    }
}

/// Talks to the Laravel REST API. Foundation only, so it also runs (and is tested) on Linux.
public final class APIClient: @unchecked Sendable {
    public var baseURL: URL?
    public var token: String?
    /// The app this build is (school|staff|parent|student) – sent on login so the server checks the role.
    public let appRole: String
    /// Called when the server answers 401 so the app can return to the login screen.
    public var onUnauthorized: (() -> Void)?

    private let session: URLSession

    /// Keys are kept exactly as the server sends them (snake_case); models read them with `Row` helpers,
    /// and dynamic records keep their field names untouched.
    public let decoder = JSONDecoder()
    private let encoder = JSONEncoder()

    public init(baseURL: URL?, token: String? = nil, appRole: String, session: URLSession = .shared) {
        self.baseURL = baseURL
        self.token = token
        self.appRole = appRole
        self.session = session
    }

    /// Normalises what the user typed into a usable API base, e.g. "dav.example.com" → "https://dav.example.com/api/v1/".
    public static func normalizedBase(_ raw: String) -> URL? {
        var s = raw.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !s.isEmpty else { return nil }
        if !s.lowercased().hasPrefix("http://") && !s.lowercased().hasPrefix("https://") { s = "https://" + s }
        if !s.contains("/api/") {
            while s.hasSuffix("/") { s.removeLast() }
            s += "/api/v1/"
        }
        if !s.hasSuffix("/") { s += "/" }
        return URL(string: s)
    }

    /// The server root (without /api/v1/), used for portal/storage links.
    public var serverRoot: URL? {
        guard let b = baseURL?.absoluteString, let r = b.range(of: "/api/") else { return baseURL }
        return URL(string: String(b[..<r.lowerBound]) + "/")
    }

    // MARK: URL building

    public func url(_ path: String, query: [String: String?] = [:]) throws -> URL {
        guard let base = baseURL else { throw APIError.notConfigured }
        let clean = path.hasPrefix("/") ? String(path.dropFirst()) : path
        guard var comps = URLComponents(url: base.appendingPathComponent(clean), resolvingAgainstBaseURL: false) else {
            throw APIError.notConfigured
        }
        let items = query.compactMap { k, v -> URLQueryItem? in
            guard let v = v, !v.isEmpty else { return nil }
            return URLQueryItem(name: k, value: v)
        }.sorted { $0.name < $1.name }
        if !items.isEmpty { comps.queryItems = items }
        guard let u = comps.url else { throw APIError.notConfigured }
        return u
    }

    /// URL that opens a file with the token in the query (`?token=`), for share links or a web view.
    public func tokenURL(_ path: String, query: [String: String?] = [:]) throws -> URL {
        var q = query
        q["token"] = token
        return try url(path, query: q)
    }

    // MARK: Requests

    public func get<T: Decodable>(_ path: String, query: [String: String?] = [:], as: T.Type = T.self) async throws -> T {
        let req = try makeRequest("GET", path, query: query)
        return try decode(T.self, from: await send(req))
    }

    /// GET returning the `data` of the envelope.
    public func getData<T: Decodable>(_ path: String, query: [String: String?] = [:], as: T.Type = T.self) async throws -> T {
        let env: Envelope<T> = try await get(path, query: query)
        return env.data
    }

    public func post<T: Decodable, B: Encodable>(_ path: String, body: B, as: T.Type = T.self) async throws -> T {
        var req = try makeRequest("POST", path)
        req.httpBody = try encoder.encode(body)
        req.setValue("application/json", forHTTPHeaderField: "Content-Type")
        return try decode(T.self, from: await send(req))
    }

    public func post<T: Decodable>(_ path: String, as: T.Type = T.self) async throws -> T {
        var req = try makeRequest("POST", path)
        req.httpBody = "{}".data(using: .utf8)
        req.setValue("application/json", forHTTPHeaderField: "Content-Type")
        return try decode(T.self, from: await send(req))
    }

    public func put<T: Decodable, B: Encodable>(_ path: String, body: B, as: T.Type = T.self) async throws -> T {
        var req = try makeRequest("PUT", path)
        req.httpBody = try encoder.encode(body)
        req.setValue("application/json", forHTTPHeaderField: "Content-Type")
        return try decode(T.self, from: await send(req))
    }

    @discardableResult
    public func delete(_ path: String) async throws -> String? {
        let req = try makeRequest("DELETE", path)
        let data = try await send(req)
        return (try? decoder.decode(MessageOnly.self, from: data))?.message
    }

    /// Multipart upload (photos, documents, Excel import files).
    public func upload<T: Decodable>(_ path: String, files: [UploadFile], fields: [String: String] = [:], as: T.Type = T.self) async throws -> T {
        var req = try makeRequest("POST", path)
        let boundary = "DAVBoundary-\(UUID().uuidString)"
        req.setValue("multipart/form-data; boundary=\(boundary)", forHTTPHeaderField: "Content-Type")
        var body = Data()
        func line(_ s: String) { body.append(s.data(using: .utf8)!) }
        for (k, v) in fields.sorted(by: { $0.key < $1.key }) {
            line("--\(boundary)\r\nContent-Disposition: form-data; name=\"\(k)\"\r\n\r\n\(v)\r\n")
        }
        for f in files {
            line("--\(boundary)\r\nContent-Disposition: form-data; name=\"\(f.field)\"; filename=\"\(f.fileName)\"\r\nContent-Type: \(f.mimeType)\r\n\r\n")
            body.append(f.data)
            line("\r\n")
        }
        line("--\(boundary)--\r\n")
        req.httpBody = body
        req.timeoutInterval = 120
        return try decode(T.self, from: await send(req))
    }

    /// Downloads a file (PDF / xlsx / zip) with the bearer token into a temporary file.
    public func download(_ path: String, query: [String: String?] = [:], fallbackName: String) async throws -> DownloadedFile {
        var req = try makeRequest("GET", path, query: query)
        req.setValue("application/pdf, application/octet-stream, */*", forHTTPHeaderField: "Accept")
        req.timeoutInterval = 120
        let (data, response) = try await perform(req)
        try check(data: data, response: response)
        let http = response as? HTTPURLResponse
        let mime = http?.value(forHTTPHeaderField: "Content-Type")
        var name = fallbackName
        if let cd = http?.value(forHTTPHeaderField: "Content-Disposition"),
           let r = cd.range(of: "filename=") {
            let n = cd[r.upperBound...].split(separator: ";").first.map(String.init) ?? ""
            let trimmed = n.trimmingCharacters(in: CharacterSet(charactersIn: "\"' "))
            if !trimmed.isEmpty { name = trimmed }
        }
        if !name.contains(".") {
            if mime?.contains("pdf") == true { name += ".pdf" }
            else if mime?.contains("spreadsheet") == true { name += ".xlsx" }
            else if mime?.contains("csv") == true { name += ".csv" }
            else if mime?.contains("zip") == true { name += ".zip" }
        }
        let dir = FileManager.default.temporaryDirectory.appendingPathComponent("DAVDownloads", isDirectory: true)
        try? FileManager.default.createDirectory(at: dir, withIntermediateDirectories: true)
        let dest = dir.appendingPathComponent(name.replacingOccurrences(of: "/", with: "-"))
        try? FileManager.default.removeItem(at: dest)
        try data.write(to: dest)
        return DownloadedFile(url: dest, mimeType: mime, suggestedName: name)
    }

    // MARK: Plumbing

    private func makeRequest(_ method: String, _ path: String, query: [String: String?] = [:]) throws -> URLRequest {
        var req = URLRequest(url: try url(path, query: query))
        req.httpMethod = method
        req.timeoutInterval = 45
        req.setValue("application/json", forHTTPHeaderField: "Accept")
        req.setValue("DAVSchool-iOS/\(appRole)", forHTTPHeaderField: "X-Client")
        if let t = token, !t.isEmpty { req.setValue("Bearer \(t)", forHTTPHeaderField: "Authorization") }
        return req
    }

    private func perform(_ req: URLRequest) async throws -> (Data, URLResponse) {
        do {
            #if canImport(FoundationNetworking)
            return try await withCheckedThrowingContinuation { cont in
                session.dataTask(with: req) { data, resp, err in
                    if let err = err { cont.resume(throwing: err); return }
                    let r: URLResponse = resp ?? URLResponse(url: req.url!, mimeType: nil, expectedContentLength: 0, textEncodingName: nil)
                    cont.resume(returning: (data ?? Data(), r))
                }.resume()
            }
            #else
            return try await session.data(for: req)
            #endif
        } catch let e as APIError {
            throw e
        } catch {
            throw APIError.offline(error.localizedDescription)
        }
    }

    private func send(_ req: URLRequest) async throws -> Data {
        let (data, response) = try await perform(req)
        try check(data: data, response: response)
        return data
    }

    private func check(data: Data, response: URLResponse) throws {
        guard let http = response as? HTTPURLResponse else { return }
        guard !(200..<300).contains(http.statusCode) else { return }
        let body = try? decoder.decode(ErrorBody.self, from: data)
        let msg = body?.message ?? ""
        switch http.statusCode {
        case 401:
            onUnauthorized?()
            throw APIError.unauthorized(msg)
        case 403: throw APIError.forbidden(msg)
        case 404: throw APIError.notFound(msg)
        case 422: throw APIError.validation(msg, body?.errors ?? [:])
        case 423: throw APIError.locked(msg)
        case 429: throw APIError.server(429, "Too many tries. Please wait a minute and try again.")
        default: throw APIError.server(http.statusCode, msg)
        }
    }

    private func decode<T: Decodable>(_ type: T.Type, from data: Data) throws -> T {
        if T.self == EmptyResponse.self { return EmptyResponse() as! T }
        do {
            return try decoder.decode(T.self, from: data)
        } catch let DecodingError.keyNotFound(key, ctx) {
            throw APIError.decoding("missing \(key.stringValue) at \(ctx.codingPath.map { $0.stringValue }.joined(separator: "."))")
        } catch let DecodingError.typeMismatch(_, ctx) {
            throw APIError.decoding("wrong type at \(ctx.codingPath.map { $0.stringValue }.joined(separator: "."))")
        } catch {
            throw APIError.decoding(String(describing: error).prefix(160).description)
        }
    }
}

/// Use when the reply body doesn't matter.
public struct EmptyResponse: Decodable { public init() {} }
