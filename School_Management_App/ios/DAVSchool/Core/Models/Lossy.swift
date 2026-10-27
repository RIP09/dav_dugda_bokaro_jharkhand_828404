import Foundation

/// A coding key built from any string, for decoding objects whose keys we choose at runtime.
public struct AnyKey: CodingKey, Hashable {
    public var stringValue: String
    public var intValue: Int?
    public init(_ s: String) { stringValue = s }
    public init?(stringValue: String) { self.stringValue = stringValue }
    public init?(intValue: Int) { self.stringValue = String(intValue); self.intValue = intValue }
}

/// Tolerant reader for one JSON object. The PHP server sometimes sends numbers as strings ("12.50"),
/// ids as "3", booleans as 0/1, so every accessor converts instead of throwing — one odd field never breaks a screen.
/// Keys are the server's snake_case names; a camelCase spelling is tried too.
public struct Row {
    let c: KeyedDecodingContainer<AnyKey>

    public init(_ decoder: Decoder) throws {
        c = try decoder.container(keyedBy: AnyKey.self)
    }

    private func key(_ name: String) -> AnyKey? {
        let k = AnyKey(name)
        if c.contains(k) { return k }
        let camel = Row.camel(name)
        if camel != name, c.contains(AnyKey(camel)) { return AnyKey(camel) }
        return nil
    }

    static func camel(_ s: String) -> String {
        let parts = s.split(separator: "_")
        guard let first = parts.first else { return s }
        return String(first) + parts.dropFirst().map { $0.prefix(1).uppercased() + $0.dropFirst() }.joined()
    }

    public func has(_ name: String) -> Bool { key(name) != nil }

    public func isNull(_ name: String) -> Bool {
        guard let k = key(name) else { return true }
        return (try? c.decodeNil(forKey: k)) ?? true
    }

    public func str(_ names: String...) -> String? {
        for n in names {
            guard let k = key(n) else { continue }
            if let s = try? c.decodeIfPresent(String.self, forKey: k) { return s }
            if let i = try? c.decodeIfPresent(Int.self, forKey: k) { return String(i) }
            if let d = try? c.decodeIfPresent(Double.self, forKey: k) { return JSONValue.format(d) }
            if let b = try? c.decodeIfPresent(Bool.self, forKey: k) { return b ? "Yes" : "No" }
        }
        return nil
    }

    public func int(_ names: String...) -> Int? {
        for n in names {
            guard let k = key(n) else { continue }
            if let i = try? c.decodeIfPresent(Int.self, forKey: k) { return i }
            if let d = try? c.decodeIfPresent(Double.self, forKey: k), d.isFinite { return Int(d) }
            if let s = try? c.decodeIfPresent(String.self, forKey: k), let v = Int(s) ?? Double(s).map({ Int($0) }) { return v }
            if let b = try? c.decodeIfPresent(Bool.self, forKey: k) { return b ? 1 : 0 }
        }
        return nil
    }

    public func double(_ names: String...) -> Double? {
        for n in names {
            guard let k = key(n) else { continue }
            if let d = try? c.decodeIfPresent(Double.self, forKey: k) { return d }
            if let s = try? c.decodeIfPresent(String.self, forKey: k), let v = Double(s) { return v }
        }
        return nil
    }

    public func bool(_ names: String...) -> Bool? {
        for n in names {
            guard let k = key(n) else { continue }
            if let b = try? c.decodeIfPresent(Bool.self, forKey: k) { return b }
            if let i = try? c.decodeIfPresent(Int.self, forKey: k) { return i != 0 }
            if let s = try? c.decodeIfPresent(String.self, forKey: k) { return ["1", "true", "yes"].contains(s.lowercased()) }
        }
        return nil
    }

    public func decode<T: Decodable>(_ type: T.Type, _ names: String...) -> T? {
        for n in names {
            guard let k = key(n) else { continue }
            if let v = try? c.decodeIfPresent(type, forKey: k) { return v }
        }
        return nil
    }

    /// Array where bad elements are skipped instead of failing the whole list.
    public func list<T: Decodable>(_ type: T.Type, _ name: String) -> [T] {
        guard let k = key(name), let wrapped = try? c.decodeIfPresent([Lenient<T>].self, forKey: k) else { return [] }
        return wrapped.compactMap { $0.value }
    }

    public func json(_ name: String) -> JSONValue? { decode(JSONValue.self, name) }
}

/// Wraps one element so a malformed element becomes nil rather than an error.
public struct Lenient<T: Decodable>: Decodable {
    public let value: T?
    public init(from decoder: Decoder) throws { value = try? T(from: decoder) }
}

/// Money in Indian format: ₹1,25,000.
public enum Money {
    public static func format(_ value: Double?) -> String {
        guard let v = value else { return "—" }
        let neg = v < 0
        let whole = Int(abs(v).rounded(.down))
        let paise = Int(((abs(v) - Double(whole)) * 100).rounded())
        var digits = String(whole)
        var out = ""
        if digits.count > 3 {
            let last3 = String(digits.suffix(3))
            digits = String(digits.dropLast(3))
            var groups: [String] = []
            while digits.count > 2 { groups.insert(String(digits.suffix(2)), at: 0); digits = String(digits.dropLast(2)) }
            if !digits.isEmpty { groups.insert(digits, at: 0) }
            out = groups.joined(separator: ",") + "," + last3
        } else { out = digits }
        if paise > 0 { out += String(format: ".%02d", paise) }
        return (neg ? "-₹" : "₹") + out
    }
}
