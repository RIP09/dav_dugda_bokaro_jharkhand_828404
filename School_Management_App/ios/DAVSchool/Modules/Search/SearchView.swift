import SwiftUI

/// Global search across students, staff, parents, books, invoices, receipts … (role-scoped by the server).
struct SearchView: View {
    @EnvironmentObject private var session: SessionStore
    @State private var query = ""
    @State private var hits: [SearchHit] = []
    @State private var searching = false
    @State private var error: String?

    var body: some View {
        List {
            if query.count < 2 {
                Section {
                    Label("Type a name, admission no., phone, receipt or book title", systemImage: "magnifyingglass")
                        .font(.subheadline).foregroundStyle(.secondary)
                }
            } else if searching {
                HStack { Spacer(); ProgressView(); Spacer() }
            } else if let e = error {
                Text(e).foregroundStyle(Theme.danger)
            } else if hits.isEmpty {
                Text("Nothing found for “\(query)”.").foregroundStyle(.secondary)
            }
            ForEach(grouped, id: \.0) { group in
                Section(group.0) {
                    ForEach(group.1) { h in
                        NavigationLink(value: route(h)) {
                            HStack(spacing: 12) {
                                Image(systemName: ModuleArea.area(forModule: h.module)?.symbol ?? "doc")
                                    .foregroundStyle(Theme.brand).frame(width: 28)
                                VStack(alignment: .leading) {
                                    Text(h.title).font(.subheadline.weight(.semibold))
                                    if let s = h.subtitle { Text(s).font(.caption).foregroundStyle(.secondary) }
                                }
                            }
                        }
                    }
                }
            }
        }
        .navigationTitle("Search")
        .searchable(text: $query, placement: .navigationBarDrawer(displayMode: .always), prompt: "Search the school")
        .task(id: query) {
            guard query.count >= 2 else { hits = []; return }
            try? await Task.sleep(nanoseconds: 350_000_000)
            guard !Task.isCancelled else { return }
            searching = true; defer { searching = false }
            do { hits = try await session.api.search(query); error = nil }
            catch is CancellationError {}
            catch { self.error = error.localizedDescription }
        }
    }

    private var grouped: [(String, [SearchHit])] {
        var out: [(String, [SearchHit])] = []
        for h in hits {
            let title = session.module(h.module)?.title ?? h.module.replacingOccurrences(of: "_", with: " ").capitalized
            if let i = out.firstIndex(where: { $0.0 == title }) { out[i].1.append(h) } else { out.append((title, [h])) }
        }
        return out
    }

    private func route(_ h: SearchHit) -> Route {
        if let s = h.screen, session.module(s) == nil, s != h.module { return .screen(s, title: h.title) }
        return .record(h.module, id: h.recordId)
    }
}
