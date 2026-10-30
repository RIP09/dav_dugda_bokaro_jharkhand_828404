import Foundation

/// One entry of `GET meta/modules` – describes a module so generic list/detail/form screens can be built for it.
public struct ModuleMeta: Decodable, Identifiable, Hashable {
    public var id: String { key }
    public let key: String
    public let title: String
    public let group: String?
    public let icon: String?
    public let labelField: String?
    public let subtitleFields: [String]
    public let permissions: ModulePermissions
    public let lockable: Bool
    public let lockNote: String?
    public let fields: [FieldMeta]
    public let actions: [ModuleAction]

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        key = r.str("key") ?? "unknown"
        title = r.str("title") ?? key.replacingOccurrences(of: "_", with: " ").capitalized
        group = r.str("group")
        icon = r.str("icon")
        labelField = r.str("label_field")
        subtitleFields = r.decode([String].self, "subtitle_fields") ?? []
        permissions = r.decode(ModulePermissions.self, "permissions") ?? ModulePermissions.viewOnly
        lockable = r.bool("lockable") ?? false
        lockNote = r.str("lock_note")
        fields = r.list(FieldMeta.self, "fields")
        actions = r.list(ModuleAction.self, "actions")
    }

    public var listFields: [FieldMeta] { fields.filter { $0.list } }
    public var filterFields: [FieldMeta] { fields.filter { $0.filter } }
    /// Fields that appear on create/edit forms.
    public var formFields: [FieldMeta] { fields.filter { !$0.readonly && $0.type != .json } }
    public func field(_ name: String) -> FieldMeta? { fields.first { $0.name == name } }
}

public struct ModulePermissions: Decodable, Hashable {
    public let view: Bool
    public let create: Bool
    public let update: Bool
    public let delete: Bool
    public let export: Bool
    public let `import`: Bool
    /// May open a record's edit history (who/when/old→new). Admins always can.
    public let history: Bool

    public static let viewOnly = ModulePermissions(view: true, create: false, update: false, delete: false, export: false, import: false, history: false)

    public init(view: Bool, create: Bool, update: Bool, delete: Bool, export: Bool, import: Bool, history: Bool) {
        self.view = view; self.create = create; self.update = update; self.delete = delete; self.export = export; self.import = `import`
        self.history = history
    }

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        view = r.bool("view") ?? true
        create = r.bool("create") ?? false
        update = r.bool("update") ?? false
        delete = r.bool("delete") ?? false
        export = r.bool("export") ?? false
        `import` = r.bool("import") ?? false
        history = r.bool("history") ?? false
    }
}

public enum FieldType: String, Hashable {
    case text, textarea, number, money, date, time, datetime, select, bool, ref, email, phone, file, image, json
    case unknown

    public init(raw: String?) { self = FieldType(rawValue: raw ?? "text") ?? .unknown }
}

public struct FieldOption: Decodable, Hashable, Identifiable {
    public var id: String { value }
    public let value: String
    public let label: String

    public init(value: String, label: String) { self.value = value; self.label = label }

    public init(from decoder: Decoder) throws {
        // Options may be {"value":..,"label":..} or plain strings.
        if let s = try? decoder.singleValueContainer().decode(String.self) {
            value = s; label = s.replacingOccurrences(of: "_", with: " ").capitalized
            return
        }
        let r = try Row(decoder)
        value = r.str("value", "id") ?? ""
        label = r.str("label", "name") ?? value
    }
}

public struct FieldRef: Decodable, Hashable {
    public let module: String
    public let labelField: String?
    /// depends_on (server v2.0.1+): this picker is filtered by another form field, e.g. Section ← Class.
    public let dependsOnField: String?
    public let dependsOnKey: String?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        module = r.str("module") ?? ""
        labelField = r.str("label_field")
        let dep = r.json("depends_on")
        dependsOnField = dep?["field"]?.stringValue
        dependsOnKey = dep?["key"]?.stringValue
    }

    /// The parent field (and the option-row column to match) — from the server, or worked out the same way for older servers.
    public func dependency(field name: String, among names: [String]) -> (field: String, key: String)? {
        if let f = dependsOnField, let k = dependsOnKey { return (f, k) }
        if module == "sections" || module == "subjects" {
            let p = name.replacingOccurrences(of: "(section|subject)_id$", with: "class_id", options: .regularExpression)
            return (p != name && names.contains(p)) ? (p, "class_id") : nil
        }
        if module == "students" {
            let pre = name.hasSuffix("student_id") ? String(name.dropLast("student_id".count)) : name
            if names.contains(pre + "section_id") { return (pre + "section_id", "section_id") }
            if names.contains(pre + "class_id") { return (pre + "class_id", "class_id") }
        }
        return nil
    }
}

public struct FieldMeta: Decodable, Hashable, Identifiable {
    public var id: String { name }
    public let name: String
    public let label: String
    public let type: FieldType
    public let required: Bool
    public let list: Bool
    public let filter: Bool
    public let readonly: Bool
    public let immutable: Bool
    public let options: [FieldOption]
    public let ref: FieldRef?
    public let help: String?
    public let defaultValue: JSONValue?
    /// e.g. a password: can be typed on the form, never shown back.
    public let writeOnly: Bool

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        writeOnly = r.bool("write_only") ?? false
        name = r.str("name") ?? "field"
        label = r.str("label") ?? name.replacingOccurrences(of: "_", with: " ").capitalized
        type = FieldType(raw: r.str("type"))
        required = r.bool("required") ?? false
        list = r.bool("list") ?? false
        filter = r.bool("filter") ?? false
        readonly = r.bool("readonly") ?? (type == .json)
        immutable = r.bool("immutable") ?? false
        options = r.list(FieldOption.self, "options")
        ref = r.decode(FieldRef.self, "ref")
        help = r.str("help")
        defaultValue = r.json("default")
    }
}

/// A module action: `post` runs `records/{module}/{id}/actions/{key}`, `pdf` opens a PDF.
public struct ModuleAction: Decodable, Hashable, Identifiable {
    public var id: String { key }
    public let key: String
    public let label: String
    public let kind: String
    public let confirm: String?
    public let when: String?
    /// Some actions need a reason (cancel receipt / certificate, reject leave).
    public let needsReason: Bool
    /// Server path, may contain `{id}` (e.g. "pdf/receipt/{id}").
    public let url: String?
    /// Extra values to ask before running (e.g. amount + mode for "Collect payment").
    public let inputs: [FieldMeta]

    public var pdfPath: String? { url }

    /// The path for one record, `{id}` filled in, without a leading slash.
    public func path(for id: Int) -> String? {
        guard var u = url else { return nil }
        u = u.replacingOccurrences(of: "{id}", with: String(id))
        if let r = u.range(of: "/api/v1/") { u = String(u[r.upperBound...]) }
        while u.hasPrefix("/") { u.removeFirst() }
        return u
    }

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        key = r.str("key") ?? "action"
        label = r.str("label") ?? key.capitalized
        kind = r.str("kind") ?? "post"
        confirm = r.str("confirm")
        when = r.str("when")
        url = r.str("url", "path", "pdf")
        needsReason = r.bool("needs_reason", "reason_required", "reason") ?? (key == "cancel")
        inputs = r.list(FieldMeta.self, "inputs")
    }

    /// Evaluates the tiny `when` expression ("status!=admitted", "status=issued", "status in a,b", "!_locked").
    public func isAvailable(for record: Record) -> Bool {
        guard let w = when?.trimmingCharacters(in: .whitespaces), !w.isEmpty else { return true }
        let clauses = w.components(separatedBy: "&&").map { $0.trimmingCharacters(in: .whitespaces) }
        return clauses.allSatisfy { ModuleAction.evaluate($0, record) }
    }

    static func evaluate(_ clause: String, _ record: Record) -> Bool {
        if clause.hasPrefix("!") { return !(record[String(clause.dropFirst())]?.boolValue ?? false) }
        if let r = clause.range(of: "!=") {
            let f = String(clause[..<r.lowerBound]).trimmingCharacters(in: .whitespaces)
            let v = String(clause[r.upperBound...]).trimmingCharacters(in: .whitespaces)
            return record.string(f) != v
        }
        if let r = clause.range(of: " in ") {
            let f = String(clause[..<r.lowerBound]).trimmingCharacters(in: .whitespaces)
            let vs = clause[r.upperBound...].split(separator: ",").map { $0.trimmingCharacters(in: .whitespaces) }
            return vs.contains(record.string(f) ?? "")
        }
        if let r = clause.range(of: "==") ?? clause.range(of: "=") {
            let f = String(clause[..<r.lowerBound]).trimmingCharacters(in: .whitespaces)
            let v = String(clause[r.upperBound...]).trimmingCharacters(in: .whitespaces)
            return record.string(f) == v
        }
        return record[clause]?.boolValue ?? true
    }
}

/// `GET meta/menu`.
public struct MenuGroup: Decodable, Hashable, Identifiable {
    public var id: String { group }
    public let group: String
    public let items: [MenuItem]

    public init(group: String, items: [MenuItem]) { self.group = group; self.items = items }

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        group = r.str("group", "title") ?? "Menu"
        items = r.list(MenuItem.self, "items")
    }
}

public struct MenuItem: Decodable, Hashable, Identifiable {
    public var id: String { key + "|" + screen }
    public let key: String
    public let title: String
    public let icon: String?
    public let screen: String

    public init(key: String, title: String, icon: String?, screen: String) {
        self.key = key; self.title = title; self.icon = icon; self.screen = screen
    }

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        key = r.str("key") ?? r.str("screen") ?? "item"
        title = r.str("title", "label") ?? key.capitalized
        icon = r.str("icon")
        screen = r.str("screen") ?? key
    }
}
