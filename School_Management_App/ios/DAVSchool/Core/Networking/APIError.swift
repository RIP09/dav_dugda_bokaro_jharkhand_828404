import Foundation

/// Everything that can go wrong talking to the school server, in words a school office understands.
public enum APIError: Error, LocalizedError, Equatable {
    case notConfigured
    case offline(String)
    case unauthorized(String)          // 401 – token expired / wrong password
    case forbidden(String)             // 403 – role not allowed
    case notFound(String)              // 404
    case validation(String, [String: [String]])   // 422
    case locked(String)                // 423 – permanent record
    case server(Int, String)
    case decoding(String)

    public var errorDescription: String? {
        switch self {
        case .notConfigured: return "Server address is not set. Open Server settings on the login screen."
        case .offline(let m): return "Can't reach the school server. Check your internet. (\(m))"
        case .unauthorized(let m): return m.isEmpty ? "Your session has expired. Please sign in again." : m
        case .forbidden(let m): return m.isEmpty ? "You don't have permission for this." : m
        case .notFound(let m): return m.isEmpty ? "Not found." : m
        case .validation(let m, let errs):
            let first = errs.values.compactMap { $0.first }.first
            return first ?? (m.isEmpty ? "Please check the form." : m)
        case .locked(let m): return m.isEmpty ? "This record is locked and can't be changed." : m
        case .server(let code, let m): return m.isEmpty ? "Server error (\(code)). Please try again." : m
        case .decoding(let m): return "Unexpected reply from server. (\(m))"
        }
    }

    /// Field-level messages for forms (422).
    public var fieldErrors: [String: [String]] {
        if case .validation(_, let e) = self { return e }
        return [:]
    }

    public var isLocked: Bool { if case .locked = self { return true }; return false }
    public var isUnauthorized: Bool { if case .unauthorized = self { return true }; return false }
}

struct ErrorBody: Decodable {
    let message: String?
    let errors: [String: [String]]?
}
