import SwiftUI

/// Admin: every change in the school, newest first (who / when / old → new). Read-only.
struct AuditLogView: View {
    @EnvironmentObject private var session: SessionStore
    @State private var entries: [AuditEntry] = []
    @State private var module = ""
    @State private var action = ""
    @State private var page = 1
    @State private var lastPage = 1
    @State private var loading = false
    @State private var error: String?

    var body: some View {
        List {
            Section {
                Picker("Module", selection: $module) {
                    Text("All modules").tag("")
                    ForEach(session.modules.values.sorted { $0.title < $1.title }) { Text($0.title).tag($0.key) }
                }
                Picker("Change", selection: $action) {
                    Text("All").tag(""); Text("Created").tag("created"); Text("Edited").tag("updated"); Text("Deleted").tag("deleted")
                }
                Label("This log can't be edited or deleted by anyone.", systemImage: "lock.fill").font(.caption).foregroundStyle(Theme.locked)
            }
            if let e = error { Text(e).foregroundStyle(Theme.danger) }
            ForEach(entries) { e in
                Group {
                    if let m = e.module, let id = e.recordId, session.module(m) != nil, e.action != "deleted" {
                        NavigationLink(value: Route.record(m, id: id)) { AuditEntryRow(entry: e) }
                    } else {
                        AuditEntryRow(entry: e)
                    }
                }
                .onAppear { if e.id == entries.last?.id { Task { await more() } } }
            }
            if loading { HStack { Spacer(); ProgressView(); Spacer() } }
        }
        .navigationTitle("Edit history")
        .refreshable { await load(reset: true) }
        .task(id: "\(module)-\(action)") { await load(reset: true) }
    }

    private func load(reset: Bool) async {
        if reset { page = 1 }
        loading = true; defer { loading = false }
        do {
            let env = try await session.api.audit(module: module.isEmpty ? nil : module, action: action.isEmpty ? nil : action, page: page)
            entries = reset ? env.data : entries + env.data
            lastPage = env.meta?.lastPage ?? page
            error = nil
        } catch { self.error = error.localizedDescription }
    }

    private func more() async {
        guard page < lastPage, !loading else { return }
        page += 1
        await load(reset: false)
    }
}

/// Excel / CSV import to move offline registers in: download template → fill → preview → import.
struct DataImportView: View {
    @EnvironmentObject private var session: SessionStore
    @StateObject private var docs = DocumentLoader()
    @State private var module = "students"
    @State private var picking = false
    @State private var fileData: Data?
    @State private var fileName: String?
    @State private var preview: ImportResult?
    @State private var result: ImportResult?
    @State private var working = false
    @State private var error: String?

    private let modules: [(String, String)] = [("students", "Students"), ("staff", "Staff"), ("parents", "Parents"), ("books", "Library books"),
                                               ("inventory_items", "Inventory items"), ("fee_structures", "Fee structure"),
                                               ("transactions", "Income & expenses"), ("visitors", "Visitors")]

    var body: some View {
        Form {
            Section {
                Picker("What to import", selection: $module) { ForEach(modules, id: \.0) { Text($0.1).tag($0.0) } }
            } footer: { Text("Move your old registers in: download the template, fill it in Excel (one row per entry), then upload it here.") }

            Section("1. Template") {
                Button { docs.open(ServerDocument(title: "\(title) template", path: "import/\(module)/template", query: ["format": "xlsx"]), api: session.api) } label: {
                    HStack { Label("Download Excel template", systemImage: "tablecells"); Spacer(); if docs.loadingId != nil { ProgressView() } }
                }
            }
            Section("2. Your file") {
                Button { picking = true } label: { Label(fileName ?? "Choose filled Excel / CSV file", systemImage: "doc.badge.plus") }
            }
            Section("3. Check & import") {
                Button { Task { await run(dryRun: true) } } label: { Label("Check file (no changes)", systemImage: "checkmark.shield") }
                    .disabled(fileData == nil || working)
                Button { Task { await run(dryRun: false) } } label: { Label("Import now", systemImage: "square.and.arrow.down.fill") }
                    .disabled(fileData == nil || working || preview == nil)
                if working { ProgressView() }
            }
            if let e = error { Section { Text(e).foregroundStyle(Theme.danger) } }
            if let p = preview { resultSection("Check result", p) }
            if let r = result { resultSection("Imported", r) }
        }
        .navigationTitle("Import from Excel")
        .sheet(item: $docs.preview) { PreviewSheet(file: $0) }
        .fileImporter(isPresented: $picking, allowedContentTypes: [.spreadsheet, .commaSeparatedText, .data]) { res in
            guard case .success(let url) = res else { return }
            let ok = url.startAccessingSecurityScopedResource()
            defer { if ok { url.stopAccessingSecurityScopedResource() } }
            fileData = try? Data(contentsOf: url)
            fileName = url.lastPathComponent
            preview = nil; result = nil
        }
        .onChange(of: module) { _ in preview = nil; result = nil }
    }

    private var title: String { modules.first { $0.0 == module }?.1 ?? module }

    private func resultSection(_ title: String, _ r: ImportResult) -> some View {
        Section(title) {
            InfoRow(label: "New", value: "\(r.created)")
            InfoRow(label: "Updated", value: "\(r.updated)")
            InfoRow(label: "Rows with problems", value: "\(r.errors.count)")
            ForEach(r.errors) { e in
                Text("Row \(e.row): \(e.message)").font(.caption).foregroundStyle(Theme.danger)
            }
        }
    }

    private func run(dryRun: Bool) async {
        guard let data = fileData, let name = fileName else { return }
        working = true; error = nil; defer { working = false }
        do {
            let r = try await session.api.importFile(module: module, data: data, fileName: name, dryRun: dryRun)
            if dryRun { preview = r } else { result = r }
        } catch { self.error = error.localizedDescription }
    }
}

/// Admin: download a full backup (every table as JSON + CSV) to keep safe in Files / Drive.
struct BackupView: View {
    @EnvironmentObject private var session: SessionStore
    @StateObject private var docs = DocumentLoader()

    var body: some View {
        List {
            Section {
                Button { docs.open(ServerDocument(title: "DAV backup \(DateText.isoString(Date()))", path: "backup/download"), api: session.api) } label: {
                    HStack { Label("Download full backup (.zip)", systemImage: "externaldrive.fill.badge.icloud"); Spacer(); if docs.loadingId != nil { ProgressView() } }
                }
            } footer: {
                Text("Save the file to Files, Google Drive or email it to yourself. Do this every week. Restoring is done on the server by your technician.")
            }
            Section("Download lists") {
                ForEach(["students", "staff", "parents", "invoices", "payments", "books"], id: \.self) { m in
                    if session.can(m, \.export) {
                        Button { docs.open(.export(m, format: "xlsx", title: session.module(m)?.title ?? m), api: session.api) } label: {
                            Label(session.module(m)?.title ?? m.capitalized, systemImage: "tablecells")
                        }
                    }
                }
            }
        }
        .navigationTitle("Backup & export")
        .sheet(item: $docs.preview) { PreviewSheet(file: $0) }
        .alert("Couldn't download", isPresented: Binding(get: { docs.error != nil }, set: { if !$0 { docs.error = nil } })) {
            Button("OK", role: .cancel) {}
        } message: { Text(docs.error ?? "") }
    }
}
