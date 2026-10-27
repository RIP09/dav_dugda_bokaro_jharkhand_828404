import SwiftUI

/// Generic list for ANY module in the registry: search, filters, paging, pull-to-refresh, add, delete, export.
@MainActor
final class ModuleListModel: ObservableObject {
    @Published var rows: [Record] = []
    @Published var total: Int?
    @Published var loading = false
    @Published var error: String?
    @Published var query = ""
    @Published var filters: [String: String] = [:]
    private var page = 1
    private var lastPage = 1
    private var loadToken = UUID()

    var canLoadMore: Bool { page < lastPage }

    func load(_ api: APIClient, module: String, fixed: [String: String], reset: Bool = true) async {
        if reset { page = 1 }
        let token = UUID(); loadToken = token
        loading = true
        defer { if loadToken == token { loading = false } }
        do {
            let env = try await api.records(module, q: query.isEmpty ? nil : query, page: page,
                                            filters: filters.merging(fixed) { _, f in f })
            guard loadToken == token else { return }
            rows = reset ? env.data : rows + env.data.filter { r in !rows.contains { $0.id == r.id } }
            total = env.meta?.total
            lastPage = env.meta?.lastPage ?? page
            error = nil
        } catch is CancellationError {
        } catch {
            guard loadToken == token else { return }
            self.error = error.localizedDescription
        }
    }

    func loadMore(_ api: APIClient, module: String, fixed: [String: String]) async {
        guard canLoadMore, !loading else { return }
        page += 1
        await load(api, module: module, fixed: fixed, reset: false)
    }

    func remove(_ id: Int) { rows.removeAll { $0.id == id }; total = total.map { $0 - 1 } }
    func upsert(_ r: Record) {
        if let i = rows.firstIndex(where: { $0.id == r.id }) { rows[i] = r } else { rows.insert(r, at: 0); total = total.map { $0 + 1 } }
    }
}

struct ModuleListView: View {
    let moduleKey: String
    var title: String? = nil
    var fixedFilters: [String: String] = [:]

    @EnvironmentObject private var session: SessionStore
    @StateObject private var model = ModuleListModel()
    @StateObject private var docs = DocumentLoader()
    @State private var showCreate = false
    @State private var showFilters = false
    @State private var pendingDelete: Record?
    @State private var banner: Banner?

    private var meta: ModuleMeta? { session.module(moduleKey) }

    var body: some View {
        Group {
            if meta == nil && !session.modules.isEmpty {
                EmptyStateView(title: "Not available", message: "This module isn't available for your account.", symbol: "lock.square")
            } else if model.rows.isEmpty && model.loading {
                LoadingView()
            } else if let e = model.error, model.rows.isEmpty {
                ErrorStateView(message: e) { await reload() }
            } else if model.rows.isEmpty {
                EmptyStateView(title: "Nothing here yet",
                               message: meta?.permissions.create == true ? "Tap + to add the first one." : nil,
                               symbol: Icons.symbol(meta?.icon))
            } else {
                list
            }
        }
        .navigationTitle(title ?? meta?.title ?? moduleKey.replacingOccurrences(of: "_", with: " ").capitalized)
        .searchable(text: $model.query, prompt: "Search \((meta?.title ?? "").lowercased())")
        .onSubmit(of: .search) { Task { await reload() } }
        .onChange(of: model.query) { q in if q.isEmpty { Task { await reload() } } }
        .refreshable { await reload() }
        .task { if model.rows.isEmpty { await reload() } }
        .toolbar { toolbar }
        .sheet(isPresented: $showCreate) {
            if let meta = meta {
                RecordFormView(meta: meta, existing: nil, presets: fixedFilters) { saved in
                    model.upsert(saved)
                    banner = Banner(text: "Saved")
                }
            }
        }
        .sheet(isPresented: $showFilters) {
            if let meta = meta { FilterSheet(meta: meta, filters: $model.filters) { Task { await reload() } } }
        }
        .sheet(item: $docs.preview) { PreviewSheet(file: $0) }
        .confirmationDialog("Delete this record?", isPresented: Binding(get: { pendingDelete != nil }, set: { if !$0 { pendingDelete = nil } }),
                            titleVisibility: .visible, presenting: pendingDelete) { r in
            Button("Delete \(r.title(using: meta))", role: .destructive) { Task { await delete(r) } }
        } message: { _ in Text("This can't be undone. The change is recorded in the edit history.") }
        .banner($banner)
    }

    private var list: some View {
        List {
            if let total = model.total {
                Text("\(total) record\(total == 1 ? "" : "s")\(model.filters.isEmpty ? "" : " (filtered)")")
                    .font(.caption).foregroundStyle(.secondary)
                    .listRowBackground(Color.clear)
            }
            ForEach(model.rows) { r in
                NavigationLink(value: Route.record(moduleKey, id: r.id)) {
                    RecordRow(record: r, meta: meta)
                }
                .swipeActions(edge: .trailing, allowsFullSwipe: false) {
                    if meta?.permissions.delete == true && r.canDelete {
                        Button(role: .destructive) { pendingDelete = r } label: { Label("Delete", systemImage: "trash") }
                    }
                }
                .onAppear {
                    if r.id == model.rows.last?.id { Task { await model.loadMore(session.api, module: moduleKey, fixed: fixedFilters) } }
                }
            }
            if model.canLoadMore {
                HStack { Spacer(); ProgressView(); Spacer() }.listRowBackground(Color.clear)
            }
        }
        .listStyle(.insetGrouped)
    }

    @ToolbarContentBuilder private var toolbar: some ToolbarContent {
        ToolbarItemGroup(placement: .primaryAction) {
            if let meta = meta, !meta.filterFields.isEmpty {
                Button { showFilters = true } label: {
                    Image(systemName: model.filters.isEmpty ? "line.3.horizontal.decrease.circle" : "line.3.horizontal.decrease.circle.fill")
                }
            }
            if let meta = meta, meta.permissions.export || meta.permissions.import {
                Menu {
                    if meta.permissions.export {
                        Section("Download") {
                            Button { exportList("xlsx") } label: { Label("Excel (.xlsx)", systemImage: "tablecells") }
                            Button { exportList("pdf") } label: { Label("PDF", systemImage: "doc.richtext") }
                            Button { exportList("csv") } label: { Label("CSV", systemImage: "doc.plaintext") }
                        }
                    }
                    if meta.permissions.import {
                        NavigationLink(value: Route.screen("import", title: "Import")) {
                            Label("Import from Excel", systemImage: "square.and.arrow.down.on.square")
                        }
                    }
                } label: {
                    if docs.loadingId != nil { ProgressView() } else { Image(systemName: "ellipsis.circle") }
                }
            }
            if meta?.permissions.create == true {
                Button { showCreate = true } label: { Image(systemName: "plus.circle.fill") }
                    .accessibilityLabel("Add")
            }
        }
    }

    private func reload() async {
        await model.load(session.api, module: moduleKey, fixed: fixedFilters)
    }

    private func exportList(_ format: String) {
        let doc = ServerDocument.export(moduleKey, format: format, title: "\(meta?.title ?? moduleKey) export",
                                        filters: model.filters.merging(fixedFilters) { _, f in f })
        docs.open(doc, api: session.api)
    }

    private func delete(_ r: Record) async {
        do {
            try await session.api.deleteRecord(moduleKey, id: r.id)
            withAnimation { model.remove(r.id) }
            banner = Banner(text: "Deleted")
        } catch {
            banner = Banner(text: error.localizedDescription, isError: true)
        }
    }
}

/// One row: title, subtitle, list fields, lock icon.
struct RecordRow: View {
    let record: Record
    let meta: ModuleMeta?

    var body: some View {
        HStack(spacing: 12) {
            if let img = meta?.fields.first(where: { $0.type == .image && $0.list }), let url = record.fileURL(img.name) {
                AsyncImage(url: url) { $0.resizable().scaledToFill() } placeholder: { Color.gray.opacity(0.2) }
                    .frame(width: 42, height: 42).clipShape(RoundedRectangle(cornerRadius: 8))
            }
            VStack(alignment: .leading, spacing: 3) {
                HStack(spacing: 6) {
                    Text(record.title(using: meta)).font(.body.weight(.semibold)).lineLimit(1)
                    if record.isLocked { LockBadge(compact: true) }
                }
                if let sub = record.subtitle(using: meta) {
                    Text(sub).font(.caption).foregroundStyle(.secondary).lineLimit(2)
                }
                if !extraFields.isEmpty {
                    Text(extraFields.joined(separator: " · ")).font(.caption2).foregroundStyle(.tertiary).lineLimit(1)
                }
            }
            Spacer(minLength: 4)
            if let st = statusValue { StatusPill(text: st) }
        }
        .padding(.vertical, 2)
    }

    private var statusValue: String? {
        guard meta?.field("status") != nil || record["status"] != nil else { return nil }
        let v = record.display("status", meta: meta)
        return v == "—" ? nil : v
    }

    private var extraFields: [String] {
        guard let meta = meta else { return [] }
        let skip = Set([meta.labelField ?? "", "status"] + meta.subtitleFields)
        return meta.listFields.filter { !skip.contains($0.name) && $0.type != .image }
            .prefix(3)
            .compactMap { f in
                let v = record.display(f.name, meta: meta)
                return v == "—" ? nil : "\(f.label): \(v)"
            }
    }
}

/// Filters built from fields marked `filter:true`.
struct FilterSheet: View {
    let meta: ModuleMeta
    @Binding var filters: [String: String]
    let apply: () -> Void
    @Environment(\.dismiss) private var dismiss
    @State private var draft: [String: String] = [:]

    var body: some View {
        NavigationStack {
            Form {
                ForEach(meta.filterFields) { f in
                    switch f.type {
                    case .select:
                        Picker(f.label, selection: binding(f.name)) {
                            Text("Any").tag("")
                            ForEach(f.options) { Text($0.label).tag($0.value) }
                        }
                    case .bool:
                        Picker(f.label, selection: binding(f.name)) {
                            Text("Any").tag(""); Text("Yes").tag("1"); Text("No").tag("0")
                        }
                    case .ref:
                        RefFilterRow(field: f, value: binding(f.name))
                    case .date:
                        DateFilterRow(label: f.label, value: binding(f.name))
                    default:
                        TextField(f.label, text: binding(f.name))
                    }
                }
            }
            .navigationTitle("Filter")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) { Button("Clear") { draft = [:] } }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Apply") { filters = draft.filter { !$0.value.isEmpty }; apply(); dismiss() }
                }
            }
            .onAppear { draft = filters }
        }
        .presentationDetents([.medium, .large])
    }

    private func binding(_ name: String) -> Binding<String> {
        Binding(get: { draft[name] ?? "" }, set: { draft[name] = $0 })
    }
}

struct RefFilterRow: View {
    let field: FieldMeta
    @Binding var value: String
    @State private var label = ""
    @State private var picking = false

    var body: some View {
        Button { picking = true } label: {
            HStack { Text(field.label).foregroundStyle(.primary); Spacer(); Text(value.isEmpty ? "Any" : (label.isEmpty ? "#\(value)" : label)).foregroundStyle(.secondary) }
        }
        .sheet(isPresented: $picking) {
            if let ref = field.ref {
                RefPickerView(ref: ref, title: field.label) { id, l in value = id.map(String.init) ?? ""; label = l ?? "" }
            }
        }
    }
}

struct DateFilterRow: View {
    let label: String
    @Binding var value: String
    var body: some View {
        HStack {
            Toggle(label, isOn: Binding(get: { !value.isEmpty }, set: { value = $0 ? DateText.isoString(Date()) : "" }))
        }
        if !value.isEmpty {
            DatePicker("Date", selection: Binding(get: { DateText.date(value) ?? Date() }, set: { value = DateText.isoString($0) }), displayedComponents: .date)
        }
    }
}
