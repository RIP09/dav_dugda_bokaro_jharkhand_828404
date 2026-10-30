import SwiftUI
import PhotosUI
import UniformTypeIdentifiers

/// Create / edit form built from the module's field list. On edit only changed fields are sent;
/// `immutable` fields (admission no., receipt no. …) can be set once and are locked afterwards.
struct RecordFormView: View {
    let meta: ModuleMeta
    let existing: Record?
    var presets: [String: String] = [:]
    let onSaved: (Record) -> Void

    @EnvironmentObject private var session: SessionStore
    @Environment(\.dismiss) private var dismiss
    @State private var values: [String: JSONValue] = [:]
    @State private var labels: [String: String] = [:]
    @State private var original: [String: JSONValue] = [:]
    @State private var fieldErrors: [String: [String]] = [:]
    @State private var error: String?
    @State private var saving = false

    private var isEdit: Bool { existing != nil }

    var body: some View {
        NavigationStack {
            Form {
                if let r = existing, r.isLocked {
                    Section { LockBadge(reason: r.lockReason ?? meta.lockNote) }
                }
                if let e = error {
                    Section { Label(e, systemImage: "exclamationmark.triangle.fill").foregroundStyle(Theme.danger) }
                }
                ForEach(groups, id: \.0) { group in
                    Section(group.0) {
                        ForEach(group.1) { f in
                            VStack(alignment: .leading, spacing: 4) {
                                FieldInput(field: f,
                                           value: binding(f.name),
                                           label: labelBinding(f.name),
                                           disabled: isDisabled(f),
                                           parent: parentOf(f))
                                if isEdit && f.immutable {
                                    Label("Can't be changed after saving", systemImage: "lock.fill").font(.caption2).foregroundStyle(.secondary)
                                } else if let h = f.help {
                                    Text(h).font(.caption2).foregroundStyle(.secondary)
                                }
                                if let errs = fieldErrors[f.name], let first = errs.first {
                                    Text(first).font(.caption).foregroundStyle(Theme.danger)
                                }
                            }
                        }
                    }
                }
                if isEdit {
                    Section {
                        Label("Your change will be saved in the edit history with your name and the time.", systemImage: "clock.arrow.circlepath")
                            .font(.footnote).foregroundStyle(.secondary)
                    }
                }
            }
            .navigationTitle(isEdit ? "Edit \(meta.title.lowercased())" : "New \(singular)")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) { Button("Cancel") { dismiss() } }
                ToolbarItem(placement: .confirmationAction) {
                    if saving { ProgressView() } else {
                        Button("Save") { Task { await save() } }.fontWeight(.semibold)
                            .disabled(existing?.isLocked == true)
                    }
                }
            }
            .interactiveDismissDisabled(saving)
            .onAppear(perform: prefill)
        }
    }

    private var singular: String {
        let t = meta.title.lowercased()
        return t.hasSuffix("ies") ? String(t.dropLast(3)) + "y" : (t.hasSuffix("s") ? String(t.dropLast()) : t)
    }

    /// Splits long forms into readable sections: main fields, then dates/money, then notes/files.
    private var groups: [(String, [FieldMeta])] {
        let fields = meta.formFields
        let main = fields.filter { ![.textarea, .file, .image].contains($0.type) }
        let extra = fields.filter { [.textarea, .file, .image].contains($0.type) }
        var out: [(String, [FieldMeta])] = [("Details", main)]
        if !extra.isEmpty { out.append(("Notes & files", extra)) }
        return out.filter { !$0.1.isEmpty }
    }

    private func isDisabled(_ f: FieldMeta) -> Bool {
        if existing?.isLocked == true { return true }
        if isEdit && f.immutable { return true }
        return f.readonly
    }

    private func binding(_ name: String) -> Binding<JSONValue?> {
        Binding(get: { values[name] }, set: { newValue in
            let changed = values[name] != newValue
            values[name] = newValue; fieldErrors[name] = nil
            // Changing the Class clears a Section / Subject / Student that belonged to the previous class.
            if changed { for child in dependents(of: name) { values[child] = .null; labels[child] = nil } }
        })
    }

    private var fieldNames: [String] { meta.formFields.map { $0.name } }

    /// The picker this ref field waits on (Section ← Class), with the chosen parent value, if any.
    private func parentOf(_ f: FieldMeta) -> RefParent? {
        guard f.type == .ref, let ref = f.ref, let dep = ref.dependency(field: f.name, among: fieldNames) else { return nil }
        let title = meta.formFields.first { $0.name == dep.field }?.label ?? "Class"
        let v = values[dep.field].flatMap { $0.isNull ? nil : ($0.intValue.map { String($0) } ?? $0.stringValue) }
        return RefParent(title: title, key: dep.key, value: v)
    }

    private func dependents(of name: String) -> [String] {
        meta.formFields.filter { f in f.type == .ref && f.ref?.dependency(field: f.name, among: fieldNames)?.field == name }.map { $0.name }
    }

    private func labelBinding(_ name: String) -> Binding<String?> {
        Binding(get: { labels[name] }, set: { labels[name] = $0 })
    }

    private func prefill() {
        guard values.isEmpty else { return }
        if let r = existing {
            for f in meta.formFields where !f.writeOnly {
                if let v = r[f.name] {
                    // ref fields may come back as an object {id, name}
                    if f.type == .ref, let o = v.objectValue, let id = o["id"] { values[f.name] = id; labels[f.name] = JSONValue.object(o).displayString }
                    else { values[f.name] = v }
                }
                if let l = r.string(f.name + "_label") { labels[f.name] = l }
            }
            original = values
        } else {
            for f in meta.formFields {
                if let p = presets[f.name] { values[f.name] = f.type == .ref || f.type == .number ? (Double(p).map { JSONValue.number($0) } ?? JSONValue.string(p)) : JSONValue.string(p) }
                else if let d = f.defaultValue, !d.isNull { values[f.name] = d }
                else if f.type == .bool { values[f.name] = .bool(false) }
                else if f.type == .date && f.required { values[f.name] = .string(DateText.isoString(Date())) }
                else if f.type == .select && f.required, let first = f.options.first { values[f.name] = .string(first.value) }
            }
        }
    }

    private func validate() -> Bool {
        var errs: [String: [String]] = [:]
        for f in meta.formFields where f.required && !isDisabled(f) {
            let v = values[f.name]
            let empty = v == nil || v!.isNull || (v!.stringValue?.trimmingCharacters(in: .whitespaces).isEmpty ?? false)
            if empty { errs[f.name] = ["\(f.label) is required."] }
        }
        fieldErrors = errs
        return errs.isEmpty
    }

    private func save() async {
        error = nil
        guard validate() else { error = "Please fill the required fields."; return }
        var body: JSONBody = [:]
        for f in meta.formFields where !isDisabled(f) {
            let v = values[f.name] ?? .null
            if isEdit {
                if v != (original[f.name] ?? .null) { body[f.name] = v }
            } else if !v.isNull {
                body[f.name] = v
            }
        }
        if isEdit && body.isEmpty { dismiss(); return }
        saving = true; defer { saving = false }
        do {
            let saved: Record
            if let r = existing { saved = try await session.api.updateRecord(meta.key, id: r.id, body) }
            else { saved = try await session.api.createRecord(meta.key, body) }
            onSaved(saved)
            dismiss()
        } catch let e as APIError {
            fieldErrors = e.fieldErrors
            error = e.errorDescription
        } catch {
            self.error = error.localizedDescription
        }
    }
}

/// The picker a ref field depends on: e.g. Section waits for Class and then lists only that class's sections.
struct RefParent {
    let title: String
    let key: String
    let value: String?
}

/// One input for one field type.
struct FieldInput: View {
    let field: FieldMeta
    @Binding var value: JSONValue?
    @Binding var label: String?
    var disabled = false
    var parent: RefParent? = nil

    @EnvironmentObject private var session: SessionStore
    @State private var pickingRef = false
    @State private var photoItem: PhotosPickerItem?
    @State private var importing = false
    @State private var uploading = false
    @State private var uploadError: String?

    private var title: String { field.label + (field.required ? " *" : "") }

    var body: some View {
        Group {
            switch field.type {
            case .textarea:
                VStack(alignment: .leading, spacing: 4) {
                    Text(title).font(.caption).foregroundStyle(.secondary)
                    TextField(field.label, text: text, axis: .vertical).lineLimit(3...8)
                }
            case .number:
                LabeledContent(title) {
                    TextField("0", text: text).keyboardType(.numberPad).multilineTextAlignment(.trailing)
                }
            case .money:
                LabeledContent(title) {
                    HStack(spacing: 2) {
                        Text("₹").foregroundStyle(.secondary)
                        TextField("0", text: text).keyboardType(.decimalPad).multilineTextAlignment(.trailing)
                    }
                }
            case .email:
                TextField(title, text: text).keyboardType(.emailAddress).textInputAutocapitalization(.never).autocorrectionDisabled()
            case .phone:
                TextField(title, text: text).keyboardType(.phonePad)
            case .date, .datetime, .time:
                dateInput
            case .select:
                Picker(title, selection: Binding(get: { value?.stringValue ?? "" }, set: { value = $0.isEmpty ? .null : .string($0) })) {
                    if !field.required { Text("—").tag("") }
                    ForEach(field.options) { Text($0.label).tag($0.value) }
                }
            case .bool:
                Toggle(title, isOn: Binding(get: { value?.boolValue ?? false }, set: { value = .bool($0) }))
            case .ref:
                if let p = parent, p.value == nil {
                    LabeledContent(title) { Text("Choose \(p.title) first").foregroundStyle(.tertiary) }
                } else {
                Button { pickingRef = true } label: {
                    HStack {
                        Text(title).foregroundStyle(.primary)
                        Spacer()
                        Text(refText).foregroundStyle(.secondary).lineLimit(1)
                        Image(systemName: "chevron.right").font(.caption).foregroundStyle(.tertiary)
                    }
                }
                .sheet(isPresented: $pickingRef) {
                    if let ref = field.ref {
                        RefPickerView(ref: ref, title: field.label, filters: parent.flatMap { p in p.value.map { [p.key: $0] } } ?? [:]) { id, l in
                            value = id.map { JSONValue.number(Double($0)) } ?? JSONValue.null
                            label = l
                        }
                    }
                }
                }
            case .image:
                imageInput
            case .file:
                fileInput
            case .json:
                LabeledContent(title, value: value?.displayString ?? "—")
            default:
                TextField(title, text: text)
            }
        }
        .disabled(disabled)
        .opacity(disabled ? 0.6 : 1)
    }

    private var text: Binding<String> {
        Binding(get: { value?.stringValue ?? "" }, set: { s in
            switch field.type {
            case .number: value = s.isEmpty ? JSONValue.null : (Double(s).map { JSONValue.number($0) } ?? JSONValue.string(s))
            case .money: value = s.isEmpty ? JSONValue.null : (Double(s.replacingOccurrences(of: ",", with: "")).map { JSONValue.number($0) } ?? JSONValue.string(s))
            default: value = .string(s)
            }
        })
    }

    private var refText: String {
        if let l = label, !l.isEmpty { return l }
        if let v = value, !v.isNull { return "#\(v.displayString)" }
        return "Choose…"
    }

    @ViewBuilder private var dateInput: some View {
        let has = value != nil && !(value!.isNull) && !(value!.stringValue ?? "").isEmpty
        if !field.required && !has {
            Button { setDate(Date()) } label: { HStack { Text(title).foregroundStyle(.primary); Spacer(); Text("Add").foregroundStyle(Theme.brand) } }
        } else {
            HStack {
                DatePicker(title, selection: Binding(get: { currentDate ?? Date() }, set: { setDate($0) }), displayedComponents: components)
                if !field.required {
                    Button { value = .null } label: { Image(systemName: "xmark.circle.fill").foregroundStyle(.tertiary) }.buttonStyle(.plain)
                }
            }
        }
    }

    private var components: DatePickerComponents {
        switch field.type {
        case .time: return .hourAndMinute
        case .datetime: return [.date, .hourAndMinute]
        default: return .date
        }
    }

    private var currentDate: Date? {
        guard let s = value?.stringValue else { return nil }
        switch field.type {
        case .time:
            let f = DateFormatter(); f.dateFormat = s.count > 5 ? "HH:mm:ss" : "HH:mm"
            return f.date(from: s)
        case .datetime: return DateText.dateTime(s)
        default: return DateText.date(s)
        }
    }

    private func setDate(_ d: Date) {
        let f = DateFormatter(); f.locale = Locale(identifier: "en_US_POSIX")
        switch field.type {
        case .time: f.dateFormat = "HH:mm"
        case .datetime: f.dateFormat = "yyyy-MM-dd HH:mm:ss"
        default: f.dateFormat = "yyyy-MM-dd"
        }
        value = .string(f.string(from: d))
    }

    private var imageInput: some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title).font(.caption).foregroundStyle(.secondary)
            HStack {
                if let s = label ?? value?.stringValue, s.hasPrefix("http"), let u = URL(string: s) {
                    AsyncImage(url: u) { $0.resizable().scaledToFill() } placeholder: { Color.gray.opacity(0.2) }
                        .frame(width: 56, height: 56).clipShape(RoundedRectangle(cornerRadius: 8))
                }
                PhotosPicker(selection: $photoItem, matching: .images) {
                    Label(value?.isNull == false && value != nil ? "Change photo" : "Choose photo", systemImage: "photo")
                }
                Spacer()
                if uploading { ProgressView() }
            }
            if let e = uploadError { Text(e).font(.caption).foregroundStyle(Theme.danger) }
        }
        .onChange(of: photoItem) { item in
            guard let item = item else { return }
            Task {
                guard let data = try? await item.loadTransferable(type: Data.self) else { return }
                let jpeg = UIImage(data: data).flatMap { $0.resizedForUpload().jpegData(compressionQuality: 0.8) } ?? data
                await upload(jpeg, name: "photo.jpg", mime: "image/jpeg")
            }
        }
    }

    private var fileInput: some View {
        HStack {
            VStack(alignment: .leading) {
                Text(title).font(.caption).foregroundStyle(.secondary)
                Text(value?.stringValue.map { ($0 as NSString).lastPathComponent } ?? "No file").font(.subheadline)
            }
            Spacer()
            if uploading { ProgressView() }
            Button("Choose file") { importing = true }
        }
        .fileImporter(isPresented: $importing, allowedContentTypes: [.pdf, .image, .spreadsheet, .commaSeparatedText, .plainText, .item]) { result in
            guard case .success(let url) = result else { return }
            let ok = url.startAccessingSecurityScopedResource()
            defer { if ok { url.stopAccessingSecurityScopedResource() } }
            guard let data = try? Data(contentsOf: url) else { uploadError = "Couldn't read the file."; return }
            let mime = UTType(filenameExtension: url.pathExtension)?.preferredMIMEType ?? "application/octet-stream"
            Task { await upload(data, name: url.lastPathComponent, mime: mime) }
        }
    }

    private func upload(_ data: Data, name: String, mime: String) async {
        uploading = true; uploadError = nil
        defer { uploading = false }
        do {
            let f = try await session.api.uploadFile(data: data, fileName: name, mimeType: mime, folder: field.name)
            value = .string(f.path)
            label = f.url
        } catch {
            uploadError = error.localizedDescription
        }
    }
}

extension UIImage {
    /// Keeps uploads small on slow mobile data (max 1280 px).
    func resizedForUpload(maxSide: CGFloat = 1280) -> UIImage {
        let side = max(size.width, size.height)
        guard side > maxSide else { return self }
        let scale = maxSide / side
        let newSize = CGSize(width: size.width * scale, height: size.height * scale)
        return UIGraphicsImageRenderer(size: newSize).image { _ in draw(in: CGRect(origin: .zero, size: newSize)) }
    }
}

/// Searchable list of another module's records, for `ref` fields and filters.
struct RefPickerView: View {
    let ref: FieldRef
    let title: String
    /// e.g. ["class_id": "8"] — only Class 6's sections once Class 6 is chosen.
    var filters: [String: String] = [:]
    let onPick: (Int?, String?) -> Void

    @EnvironmentObject private var session: SessionStore
    @Environment(\.dismiss) private var dismiss
    @State private var query = ""
    @State private var rows: [Record] = []
    @State private var loading = false
    @State private var error: String?

    var body: some View {
        NavigationStack {
            List {
                Button("None") { onPick(nil, nil); dismiss() }.foregroundStyle(.secondary)
                if let e = error { Text(e).foregroundStyle(Theme.danger).font(.footnote) }
                ForEach(rows) { r in
                    Button {
                        onPick(r.id, label(r))
                        dismiss()
                    } label: {
                        VStack(alignment: .leading) {
                            Text(label(r)).foregroundStyle(.primary)
                            if let s = r.subtitle(using: session.module(ref.module)) { Text(s).font(.caption).foregroundStyle(.secondary) }
                        }
                    }
                }
                if loading { HStack { Spacer(); ProgressView(); Spacer() } }
            }
            .searchable(text: $query, placement: .navigationBarDrawer(displayMode: .always))
            .task(id: query) {
                try? await Task.sleep(nanoseconds: 300_000_000)
                await load()
            }
            .navigationTitle(title)
            .navigationBarTitleDisplayMode(.inline)
            .toolbar { ToolbarItem(placement: .cancellationAction) { Button("Cancel") { dismiss() } } }
        }
    }

    private func label(_ r: Record) -> String {
        // "_option" (server v2.0.1+) tells look-alikes apart: "Class 6 – A", "Mathematics (Class 6)".
        if let v = r.string("_option"), !v.isEmpty { return v }
        if ref.module == "sections", let c = r.string("class_id_label"), let n = r.string("name"), !c.isEmpty { return "\(c) – \(n)" }
        if let lf = ref.labelField, lf != "_option", let v = r.string(lf), !v.isEmpty { return v }
        return r.title(using: session.module(ref.module))
    }

    private func load() async {
        loading = true; defer { loading = false }
        do {
            rows = try await session.api.records(ref.module, q: query.isEmpty ? nil : query, perPage: filters.isEmpty ? 50 : 200, sort: nil, filters: filters).data
            error = nil
        } catch is CancellationError {
        } catch {
            self.error = error.localizedDescription
        }
    }
}
