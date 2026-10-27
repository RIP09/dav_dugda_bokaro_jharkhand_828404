import Foundation

/// One row of a generic module (`records/{module}`): its fields plus the server's meta keys
/// `_title`, `_subtitle`, `_locked`, `_lock_reason`, `_can_edit`, `_can_delete`.
public struct Record: Decodable, Identifiable, Hashable {
    public let values: [String: JSONValue]

    public init(values: [String: JSONValue]) { self.values = values }

    public init(from decoder: Decoder) throws {
        values = try decoder.singleValueContainer().decode([String: JSONValue].self)
    }

    public subscript(_ key: String) -> JSONValue? { values[key] }

    public var id: Int { values["id"]?.intValue ?? 0 }
    public func string(_ key: String) -> String? { values[key]?.stringValue }

    public var title: String {
        if let t = values["_title"]?.stringValue, !t.isEmpty { return t }
        for k in ["name", "title", "label", "receipt_no", "invoice_no", "certificate_no"] {
            if let v = values[k]?.stringValue, !v.isEmpty { return v }
        }
        return "#\(id)"
    }

    public func title(using meta: ModuleMeta?) -> String {
        if let t = values["_title"]?.stringValue, !t.isEmpty { return t }
        if let lf = meta?.labelField { let v = display(lf, meta: meta); if v != "—" { return v } }
        return title
    }

    public func subtitle(using meta: ModuleMeta?) -> String? {
        if let s = values["_subtitle"]?.stringValue, !s.isEmpty { return s }
        guard let meta = meta else { return nil }
        let parts = meta.subtitleFields.compactMap { f -> String? in
            let v = display(f, meta: meta)
            return v == "—" ? nil : v
        }
        return parts.isEmpty ? nil : parts.joined(separator: " · ")
    }

    public var isLocked: Bool { values["_locked"]?.boolValue ?? false }
    public var lockReason: String? { values["_lock_reason"]?.stringValue }
    /// Defaults to true when the server didn't send the flag (permission still comes from meta).
    public var canEdit: Bool { !isLocked && (values["_can_edit"]?.boolValue ?? true) }
    public var canDelete: Bool { !isLocked && (values["_can_delete"]?.boolValue ?? true) }
    public var createdAt: String? { values["created_at"]?.stringValue }
    public var updatedAt: String? { values["updated_at"]?.stringValue }

    /// Text for a field, using `<name>_label` for refs and option labels for selects.
    public func display(_ name: String, meta: ModuleMeta?) -> String {
        if let lbl = values[name + "_label"]?.stringValue, !lbl.isEmpty { return lbl }
        guard let v = values[name], !v.isNull else { return "—" }
        if let f = meta?.field(name) {
            switch f.type {
            case .select:
                if let raw = v.stringValue, let o = f.options.first(where: { $0.value == raw }) { return o.label }
            case .money:
                return Money.format(v.doubleValue)
            case .bool:
                return (v.boolValue ?? false) ? "Yes" : "No"
            case .date:
                return DateText.pretty(v.stringValue)
            case .datetime:
                return DateText.prettyDateTime(v.stringValue)
            case .ref:
                if let o = v.objectValue { return JSONValue.object(o).displayString }
            case .file, .image:
                if let s = v.stringValue { return (s as NSString).lastPathComponent }
            default: break
            }
        }
        return v.displayString
    }

    /// URL of a file/image field (`<name>_url`).
    public func fileURL(_ name: String) -> URL? {
        guard let s = values[name + "_url"]?.stringValue ?? values[name]?.stringValue, s.hasPrefix("http") else { return nil }
        return URL(string: s)
    }
}

/// Date helpers: the server speaks ISO dates ("2026-10-27", "2026-10-27T08:04:31.000000Z").
public enum DateText {
    static let iso: DateFormatter = { let f = DateFormatter(); f.locale = Locale(identifier: "en_US_POSIX"); f.dateFormat = "yyyy-MM-dd"; return f }()
    static let month: DateFormatter = { let f = DateFormatter(); f.locale = Locale(identifier: "en_US_POSIX"); f.dateFormat = "yyyy-MM"; return f }()
    static let prettyF: DateFormatter = { let f = DateFormatter(); f.locale = Locale(identifier: "en_IN"); f.dateFormat = "d MMM yyyy"; return f }()
    static let prettyDT: DateFormatter = { let f = DateFormatter(); f.locale = Locale(identifier: "en_IN"); f.dateFormat = "d MMM yyyy, h:mm a"; return f }()
    static let monthTitle: DateFormatter = { let f = DateFormatter(); f.locale = Locale(identifier: "en_IN"); f.dateFormat = "MMMM yyyy"; return f }()

    public static func isoString(_ d: Date) -> String { iso.string(from: d) }
    public static func monthString(_ d: Date) -> String { month.string(from: d) }
    public static func monthTitle(_ d: Date) -> String { monthTitle.string(from: d) }
    public static func date(_ s: String?) -> Date? {
        guard let s = s, s.count >= 10 else { return nil }
        return iso.date(from: String(s.prefix(10)))
    }
    public static func dateTime(_ s: String?) -> Date? {
        guard let s = s else { return nil }
        let f = ISO8601DateFormatter()
        f.formatOptions = [.withInternetDateTime, .withFractionalSeconds]
        if let d = f.date(from: s) { return d }
        f.formatOptions = [.withInternetDateTime]
        if let d = f.date(from: s) { return d }
        let g = DateFormatter(); g.locale = Locale(identifier: "en_US_POSIX"); g.dateFormat = "yyyy-MM-dd HH:mm:ss"
        return g.date(from: s) ?? date(s)
    }
    public static func pretty(_ s: String?) -> String {
        guard let s = s, !s.isEmpty else { return "—" }
        if let d = date(s) { return prettyF.string(from: d) }
        return s
    }
    public static func prettyDateTime(_ s: String?) -> String {
        guard let s = s, !s.isEmpty else { return "—" }
        if let d = dateTime(s) { return prettyDT.string(from: d) }
        return s
    }
}
