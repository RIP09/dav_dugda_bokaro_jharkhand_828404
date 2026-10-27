import Foundation

/// A dynamic JSON value. Generic module records come back with whatever fields the module defines,
/// so they are decoded into this instead of a fixed struct.
public enum JSONValue: Codable, Hashable {
    case string(String)
    case number(Double)
    case bool(Bool)
    case object([String: JSONValue])
    case array([JSONValue])
    case null

    public init(from decoder: Decoder) throws {
        let c = try decoder.singleValueContainer()
        if c.decodeNil() { self = .null; return }
        if let b = try? c.decode(Bool.self) { self = .bool(b); return }
        if let n = try? c.decode(Double.self) { self = .number(n); return }
        if let s = try? c.decode(String.self) { self = .string(s); return }
        if let a = try? c.decode([JSONValue].self) { self = .array(a); return }
        if let o = try? c.decode([String: JSONValue].self) { self = .object(o); return }
        throw DecodingError.dataCorruptedError(in: c, debugDescription: "Unsupported JSON value")
    }

    public func encode(to encoder: Encoder) throws {
        var c = encoder.singleValueContainer()
        switch self {
        case .string(let s): try c.encode(s)
        case .number(let n):
            if n.rounded() == n && abs(n) < 9.0e15 { try c.encode(Int64(n)) } else { try c.encode(n) }
        case .bool(let b): try c.encode(b)
        case .object(let o): try c.encode(o)
        case .array(let a): try c.encode(a)
        case .null: try c.encodeNil()
        }
    }

    // MARK: Convenience accessors

    public subscript(key: String) -> JSONValue? {
        if case .object(let o) = self { return o[key] }
        return nil
    }

    public var isNull: Bool { if case .null = self { return true }; return false }

    public var stringValue: String? {
        switch self {
        case .string(let s): return s
        case .number(let n): return JSONValue.format(n)
        case .bool(let b): return b ? "true" : "false"
        default: return nil
        }
    }

    public var doubleValue: Double? {
        switch self {
        case .number(let n): return n
        case .string(let s): return Double(s.trimmingCharacters(in: .whitespaces))
        case .bool(let b): return b ? 1 : 0
        default: return nil
        }
    }

    public var intValue: Int? {
        guard let d = doubleValue, d.isFinite else { return nil }
        return Int(d)
    }

    public var boolValue: Bool? {
        switch self {
        case .bool(let b): return b
        case .number(let n): return n != 0
        case .string(let s): return ["1", "true", "yes"].contains(s.lowercased())
        default: return nil
        }
    }

    public var arrayValue: [JSONValue]? { if case .array(let a) = self { return a }; return nil }
    public var objectValue: [String: JSONValue]? { if case .object(let o) = self { return o }; return nil }

    /// Human readable text for showing a value in a list or detail screen.
    public var displayString: String {
        switch self {
        case .null: return "—"
        case .string(let s): return s.isEmpty ? "—" : s
        case .number(let n): return JSONValue.format(n)
        case .bool(let b): return b ? "Yes" : "No"
        case .array(let a): return a.map { $0.displayString }.joined(separator: ", ")
        case .object(let o):
            if let t = o["name"] ?? o["title"] ?? o["label"] { return t.displayString }
            return o.keys.sorted().map { "\($0): \(o[$0]!.displayString)" }.joined(separator: ", ")
        }
    }

    static func format(_ n: Double) -> String {
        if n.rounded() == n && abs(n) < 1e15 { return String(Int64(n)) }
        return String(format: "%.2f", n)
    }

    /// Builds a JSONValue from plain Swift values (used when sending forms).
    public static func from(_ any: Any?) -> JSONValue {
        switch any {
        case nil: return .null
        case let v as JSONValue: return v
        case let v as String: return .string(v)
        case let v as Bool: return .bool(v)
        case let v as Int: return .number(Double(v))
        case let v as Double: return .number(v)
        case let v as [Any?]: return .array(v.map { from($0) })
        case let v as [String: Any?]: return .object(v.mapValues { from($0) })
        default: return .string(String(describing: any!))
        }
    }
}
