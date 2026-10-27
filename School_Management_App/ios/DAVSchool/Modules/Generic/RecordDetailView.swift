import SwiftUI

/// Generic detail for any module record: fields, lock state, edit, delete, actions, PDFs, history.
struct RecordDetailView: View {
    let moduleKey: String
    let recordId: Int

    @EnvironmentObject private var session: SessionStore
    @Environment(\.dismiss) private var dismiss
    @StateObject private var docs = DocumentLoader()
    @State private var record: Record?
    @State private var error: String?
    @State private var showEdit = false
    @State private var showHistory = false
    @State private var confirmDelete = false
    @State private var pendingAction: ModuleAction?
    @State private var formAction: ModuleAction?
    @State private var reason = ""
    @State private var working = false
    @State private var banner: Banner?

    private var meta: ModuleMeta? { session.module(moduleKey) }
    private var canUpdate: Bool { (meta?.permissions.update ?? false) && (record?.canEdit ?? false) }
    private var canDelete: Bool { (meta?.permissions.delete ?? false) && (record?.canDelete ?? false) }
    private var canSeeHistory: Bool { session.isAdmin || (meta?.permissions.history ?? false) }

    var body: some View {
        Group {
            if let r = record { content(r) }
            else if let e = error { ErrorStateView(message: e) { await load() } }
            else { LoadingView() }
        }
        .navigationTitle(record?.title(using: meta) ?? meta?.title ?? "Details")
        .navigationBarTitleDisplayMode(.inline)
        .toolbar {
            ToolbarItemGroup(placement: .primaryAction) {
                if record != nil && canSeeHistory {
                    Button { showHistory = true } label: { Image(systemName: "clock.arrow.circlepath") }
                        .accessibilityLabel("Edit history")
                }
                if canUpdate {
                    Button("Edit") { showEdit = true }.fontWeight(.semibold)
                }
            }
        }
        .task { await load() }
        .refreshable { await load() }
        .sheet(isPresented: $showEdit) {
            if let meta = meta, let r = record {
                RecordFormView(meta: meta, existing: r) { saved in
                    record = saved
                    banner = Banner(text: "Changes saved")
                }
            }
        }
        .sheet(isPresented: $showHistory) {
            NavigationStack { HistoryView(moduleKey: moduleKey, recordId: recordId) }
        }
        .sheet(item: $docs.preview) { PreviewSheet(file: $0) }
        .sheet(item: $formAction) { a in
            ActionFormSheet(action: a) { values in
                Task { await run(a, extra: values) }
            }
        }
        .confirmationDialog("Delete this record?", isPresented: $confirmDelete, titleVisibility: .visible) {
            Button("Delete", role: .destructive) { Task { await delete() } }
        } message: { Text("This can't be undone. The change is recorded in the edit history.") }
        .alert(pendingAction?.label ?? "", isPresented: Binding(get: { pendingAction != nil }, set: { if !$0 { pendingAction = nil } })) {
            if pendingAction?.needsReason == true {
                TextField("Reason (required)", text: $reason)
            }
            Button("Cancel", role: .cancel) { reason = "" }
            Button("Confirm") {
                if let a = pendingAction { Task { await run(a) } }
            }
        } message: {
            Text(pendingAction?.confirm ?? (pendingAction?.needsReason == true ? "Please write the reason. This will be saved in the history." : "Are you sure?"))
        }
        .alert("Couldn't open", isPresented: Binding(get: { docs.error != nil }, set: { if !$0 { docs.error = nil } })) {
            Button("OK", role: .cancel) {}
        } message: { Text(docs.error ?? "") }
        .banner($banner)
    }

    @ViewBuilder private func content(_ r: Record) -> some View {
        List {
            Section {
                VStack(alignment: .leading, spacing: 10) {
                    HStack(spacing: 12) {
                        if let img = meta?.fields.first(where: { $0.type == .image }), let url = r.fileURL(img.name) {
                            AsyncImage(url: url) { $0.resizable().scaledToFill() } placeholder: { Color.gray.opacity(0.2) }
                                .frame(width: 64, height: 64).clipShape(RoundedRectangle(cornerRadius: 12))
                        } else {
                            Image(systemName: Icons.symbol(meta?.icon))
                                .font(.title2).foregroundStyle(.white)
                                .frame(width: 52, height: 52)
                                .background(Theme.brandGradient, in: RoundedRectangle(cornerRadius: 14))
                        }
                        VStack(alignment: .leading, spacing: 3) {
                            Text(r.title(using: meta)).font(.title3.weight(.bold))
                            if let s = r.subtitle(using: meta) { Text(s).font(.subheadline).foregroundStyle(.secondary) }
                            if let st = r["status"], !st.isNull { StatusPill(text: r.display("status", meta: meta)) }
                        }
                    }
                    if r.isLocked {
                        LockBadge(reason: r.lockReason ?? meta?.lockNote)
                    } else if let note = meta?.lockNote, meta?.lockable == true {
                        Label(note, systemImage: "info.circle").font(.caption).foregroundStyle(.secondary)
                    }
                }
                .padding(.vertical, 4)
            }

            Section("Details") {
                ForEach(detailFields, id: \.self) { name in
                    fieldRow(name, r)
                }
            }

            let docsList = documents(r)
            if !docsList.isEmpty {
                Section("Print / download") {
                    ForEach(docsList) { d in
                        Button { docs.open(d, api: session.api) } label: {
                            HStack {
                                Label(d.title, systemImage: "printer.fill")
                                Spacer()
                                if docs.loadingId == d.id { ProgressView() }
                            }
                        }
                    }
                }
            }

            let actions = (meta?.actions ?? []).filter { $0.kind != "pdf" && $0.isAvailable(for: r) }
            if !actions.isEmpty {
                Section("Actions") {
                    ForEach(actions) { a in
                        Button(role: a.key == "cancel" ? .destructive : nil) {
                            reason = ""
                            if a.inputs.isEmpty { pendingAction = a } else { formAction = a }
                        } label: {
                            HStack {
                                Label(a.label, systemImage: actionSymbol(a.key))
                                Spacer()
                                if working { ProgressView() }
                            }
                        }
                        .disabled(working)
                    }
                }
            }

            Section {
                if let c = r.createdAt { InfoRow(label: "Created", value: DateText.prettyDateTime(c)) }
                if let u = r.updatedAt { InfoRow(label: "Last changed", value: DateText.prettyDateTime(u)) }
                if canSeeHistory {
                    Button { showHistory = true } label: { Label("See edit history (who changed what)", systemImage: "clock.arrow.circlepath") }
                }
                if canDelete {
                    Button(role: .destructive) { confirmDelete = true } label: { Label("Delete", systemImage: "trash") }
                } else if r.isLocked {
                    Label("Editing and deleting are turned off for this record.", systemImage: "lock.fill")
                        .font(.footnote).foregroundStyle(Theme.locked)
                }
            }
        }
        .listStyle(.insetGrouped)
    }

    @ViewBuilder private func fieldRow(_ name: String, _ r: Record) -> some View {
        let f = meta?.field(name)
        let label = f?.label ?? name.replacingOccurrences(of: "_", with: " ").capitalized
        if let f = f, f.type == .image, let url = r.fileURL(name) {
            VStack(alignment: .leading) {
                Text(label).font(.subheadline).foregroundStyle(.secondary)
                AsyncImage(url: url) { $0.resizable().scaledToFit() } placeholder: { ProgressView() }
                    .frame(maxHeight: 220).clipShape(RoundedRectangle(cornerRadius: 10))
            }
        } else if let f = f, f.type == .file, let url = r.fileURL(name) {
            Link(destination: url) { InfoRow(label: label, value: r.display(name, meta: meta)) }
        } else if let f = f, f.type == .phone, let v = r.string(name), let url = URL(string: "tel:\(v.filter { $0.isNumber || $0 == "+" })") {
            Link(destination: url) { InfoRow(label: label, value: v) }
        } else {
            HStack(alignment: .firstTextBaseline) {
                InfoRow(label: label, value: r.display(name, meta: meta))
                if f?.immutable == true { Image(systemName: "lock.fill").font(.caption2).foregroundStyle(.tertiary) }
            }
        }
    }

    /// Fields to show: from meta (in order), else every non-meta key the server sent.
    private var detailFields: [String] {
        if let meta = meta, !meta.fields.isEmpty { return meta.fields.filter { !$0.writeOnly }.map(\.name) }
        let skip: Set<String> = ["id", "created_at", "updated_at", "_title", "_subtitle", "_locked", "_lock_reason", "_can_edit", "_can_delete"]
        return (record?.values.keys.filter { !skip.contains($0) && !$0.hasSuffix("_label") && !$0.hasSuffix("_url") } ?? []).sorted()
    }

    private func documents(_ r: Record) -> [ServerDocument] {
        var list: [ServerDocument] = []
        for a in meta?.actions ?? [] where a.kind == "pdf" && a.isAvailable(for: r) {
            if let p = a.path(for: r.id) { list.append(ServerDocument(title: a.label, path: p)) }
            else { list.append(defaultPDF(a, r)) }
        }
        for d in ModuleArea.documents(moduleKey: moduleKey, record: r) where !list.contains(where: { $0.path == d.path && $0.query == d.query }) {
            list.append(d)
        }
        return list
    }

    /// `kind: pdf` action without a path → the pdf/{doc}/{id} that matches this module.
    private func defaultPDF(_ a: ModuleAction, _ r: Record) -> ServerDocument {
        let doc: String
        switch moduleKey {
        case "payments": doc = "receipt"
        case "invoices": doc = "invoice"
        case "certificates": doc = "certificate"
        case "payslips": doc = "salary-slip"
        case "students": doc = a.key == "pdf" ? "id-card" : a.key.replacingOccurrences(of: "_", with: "-")
        case "staff", "teachers": doc = "staff-id-card"
        default: doc = a.key == "pdf" ? moduleKey : a.key.replacingOccurrences(of: "_", with: "-")
        }
        return .pdf(doc, id: r.id, title: a.label)
    }

    private func actionSymbol(_ key: String) -> String {
        switch key {
        case "cancel": return "xmark.octagon.fill"
        case "convert", "admit": return "person.badge.plus"
        case "issue": return "checkmark.seal.fill"
        case "pay": return "indianrupeesign.circle.fill"
        case "lock_results": return "lock.fill"
        case "publish": return "paperplane.fill"
        case "approve": return "checkmark.circle.fill"
        case "reject": return "xmark.circle.fill"
        case "return": return "arrow.uturn.left.circle.fill"
        default: return "bolt.fill"
        }
    }

    private func load() async {
        do {
            let r = try await session.api.record(moduleKey, id: recordId)
            withAnimation { record = r }
            error = nil
        } catch {
            if record == nil { self.error = error.localizedDescription }
        }
    }

    private func run(_ a: ModuleAction, extra: JSONBody = [:]) async {
        if a.needsReason && reason.trimmingCharacters(in: .whitespaces).isEmpty && extra["reason"] == nil {
            banner = Banner(text: "Please write a reason.", isError: true); return
        }
        working = true; defer { working = false; reason = "" }
        do {
            var body: JSONBody = extra
            if !reason.isEmpty { body["reason"] = .string(reason) }
            var data: JSONValue?
            var message: String?
            if let p = a.path(for: recordId), !p.hasPrefix("records/") {
                let env: Envelope<JSONValue?> = try await session.api.post(p, body: body)
                data = env.data
                message = env.message
            } else {
                let res = try await session.api.runAction(moduleKey, id: recordId, action: a.key, body: body)
                data = res.0
                message = res.1
            }
            if let d = data, case .object(let o) = d, d["id"]?.intValue == recordId {
                record = Record(values: o)
            } else {
                await load()
            }
            banner = Banner(text: message ?? "\(a.label) done")
        } catch {
            banner = Banner(text: error.localizedDescription, isError: true)
            if (error as? APIError)?.isLocked == true { await load() }
        }
    }

    private func delete() async {
        do {
            try await session.api.deleteRecord(moduleKey, id: recordId)
            dismiss()
        } catch {
            banner = Banner(text: error.localizedDescription, isError: true)
        }
    }
}

/// Audit trail of one record: who, when, old → new.
struct HistoryView: View {
    let moduleKey: String
    let recordId: Int
    @EnvironmentObject private var session: SessionStore
    @Environment(\.dismiss) private var dismiss

    var body: some View {
        AsyncContent(load: { try await session.api.history(moduleKey, id: recordId) }) { entries, reload in
            if entries.isEmpty {
                EmptyStateView(title: "No changes yet", message: "Every edit will appear here with who made it and when.", symbol: "clock")
            } else {
                List(entries) { AuditEntryRow(entry: $0, showRecord: false) }
                    .refreshable { await reload() }
            }
        }
        .navigationTitle("Edit history")
        .navigationBarTitleDisplayMode(.inline)
        .toolbar { ToolbarItem(placement: .confirmationAction) { Button("Done") { dismiss() } } }
    }
}

struct AuditEntryRow: View {
    let entry: AuditEntry
    var showRecord = true

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            HStack {
                StatusPill(text: entry.actionTitle, color: color)
                if showRecord {
                    Text([entry.module?.replacingOccurrences(of: "_", with: " ").capitalized, entry.recordTitle].compactMap { $0 }.joined(separator: " · "))
                        .font(.subheadline.weight(.semibold)).lineLimit(1)
                }
                Spacer()
            }
            Text("\(entry.userName ?? "System") · \(DateText.prettyDateTime(entry.createdAt))")
                .font(.caption).foregroundStyle(.secondary)
            ForEach(entry.changes) { c in
                HStack(alignment: .firstTextBaseline, spacing: 6) {
                    Text(c.field.replacingOccurrences(of: "_", with: " ").capitalized + ":").font(.caption.weight(.semibold))
                    Text(c.old).font(.caption).strikethrough().foregroundStyle(Theme.danger)
                    Image(systemName: "arrow.right").font(.caption2).foregroundStyle(.secondary)
                    Text(c.new).font(.caption).foregroundStyle(Theme.success)
                }
            }
        }
        .padding(.vertical, 4)
    }

    private var color: Color {
        switch entry.actionTitle {
        case "Created": return Theme.success
        case "Deleted", "Cancel": return Theme.danger
        default: return .blue
        }
    }
}


/// Asks the extra values an action needs (e.g. Collect payment → amount + mode), plus a reason when required.
struct ActionFormSheet: View {
    let action: ModuleAction
    let submit: (JSONBody) -> Void
    @Environment(\.dismiss) private var dismiss
    @State private var values: [String: JSONValue] = [:]
    @State private var labels: [String: String] = [:]
    @State private var reason = ""
    @State private var error: String?

    var body: some View {
        NavigationStack {
            Form {
                if let c = action.confirm { Section { Text(c).font(.subheadline) } }
                Section {
                    ForEach(action.inputs) { f in
                        FieldInput(field: f,
                                   value: Binding(get: { values[f.name] }, set: { values[f.name] = $0 }),
                                   label: Binding(get: { labels[f.name] }, set: { labels[f.name] = $0 }))
                    }
                    if action.needsReason {
                        TextField("Reason (required)", text: $reason, axis: .vertical)
                    }
                }
                if let e = error { Text(e).foregroundStyle(Theme.danger) }
            }
            .navigationTitle(action.label)
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) { Button("Cancel") { dismiss() } }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Confirm") {
                        for f in action.inputs where f.required {
                            if values[f.name] == nil || values[f.name]!.isNull { error = "\(f.label) is required."; return }
                        }
                        if action.needsReason && reason.trimmingCharacters(in: .whitespaces).isEmpty { error = "Please write a reason."; return }
                        var body: JSONBody = values.filter { !$0.value.isNull }
                        if action.needsReason { body["reason"] = .string(reason) }
                        submit(body)
                        dismiss()
                    }
                    .fontWeight(.semibold)
                }
            }
            .onAppear {
                for f in action.inputs where values[f.name] == nil {
                    if let d = f.defaultValue, !d.isNull { values[f.name] = d }
                    else if f.type == .select, let first = f.options.first { values[f.name] = .string(first.value) }
                }
            }
        }
        .presentationDetents([.medium, .large])
    }
}
