import SwiftUI

struct NoticesView: View {
    @EnvironmentObject private var session: SessionStore
    @State private var notices: [Notice] = []
    @State private var error: String?
    @State private var loading = true
    @State private var showCreate = false

    var body: some View {
        List {
            if loading && notices.isEmpty { HStack { Spacer(); ProgressView(); Spacer() } }
            if let e = error, notices.isEmpty { Text(e).foregroundStyle(Theme.danger) }
            ForEach(notices) { n in
                NavigationLink(value: Route.notice(n)) {
                    HStack(alignment: .top, spacing: 12) {
                        Circle().fill(n.isRead ? Color.clear : Theme.brand).frame(width: 8, height: 8).padding(.top, 6)
                        VStack(alignment: .leading, spacing: 4) {
                            HStack {
                                if n.isPinned { Image(systemName: "pin.fill").font(.caption).foregroundStyle(Theme.accent) }
                                Text(n.title).font(.subheadline.weight(n.isRead ? .regular : .semibold))
                            }
                            Text(n.body).font(.caption).foregroundStyle(.secondary).lineLimit(2)
                            Text([DateText.pretty(n.publishDate), n.authorName].compactMap { $0 }.joined(separator: " · ")).font(.caption2).foregroundStyle(.tertiary)
                        }
                    }
                }
            }
        }
        .navigationTitle("Notices")
        .refreshable { await load() }
        .task { await load() }
        .toolbar {
            if session.can("notices", \.create) {
                ToolbarItem(placement: .primaryAction) { Button { showCreate = true } label: { Image(systemName: "plus.circle.fill") } }
            }
            if session.can("notices", \.update) {
                ToolbarItem(placement: .secondaryAction) {
                    NavigationLink(value: Route.module("notices", title: "Manage notices")) { Label("Edit / manage", systemImage: "square.and.pencil") }
                }
            }
        }
        .sheet(isPresented: $showCreate) {
            if let meta = session.module("notices") {
                RecordFormView(meta: meta, existing: nil) { _ in Task { await load() } }
            }
        }
    }

    private func load() async {
        loading = true; defer { loading = false }
        do { notices = try await session.api.notices(); error = nil } catch { self.error = error.localizedDescription }
    }
}

struct NoticeDetailView: View {
    let notice: Notice
    @EnvironmentObject private var session: SessionStore
    @State private var reads: JSONValue?

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 12) {
                if notice.isPinned { Label("Pinned", systemImage: "pin.fill").font(.caption).foregroundStyle(Theme.accent) }
                Text(notice.title).font(.title2.weight(.bold))
                Text([DateText.pretty(notice.publishDate), notice.authorName, notice.audience.map { "For: \($0.capitalized)" }].compactMap { $0 }.joined(separator: " · "))
                    .font(.caption).foregroundStyle(.secondary)
                Divider()
                Text(notice.body).font(.body).textSelection(.enabled)
                if let r = reads {
                    Divider()
                    Label(readText(r), systemImage: "eye.fill").font(.subheadline).foregroundStyle(Theme.brand)
                }
            }
            .padding()
            .frame(maxWidth: .infinity, alignment: .leading)
        }
        .navigationTitle("Notice")
        .navigationBarTitleDisplayMode(.inline)
        .task {
            try? await session.api.markNoticeRead(notice.id)
            if session.isAdmin || session.user?.role == "teacher" { reads = try? await session.api.noticeReads(notice.id) }
        }
    }

    private func readText(_ r: JSONValue) -> String {
        let read = r["read"]?.intValue ?? r["read_count"]?.intValue ?? r["reads"]?.arrayValue?.count ?? r.arrayValue?.count ?? 0
        if let total = r["total"]?.intValue ?? r["audience_count"]?.intValue { return "Read by \(read) of \(total)" }
        return "Read by \(read)"
    }
}

struct CalendarView: View {
    @EnvironmentObject private var session: SessionStore

    var body: some View {
        AsyncContent(load: { try await session.api.events() }) { events, reload in
            if events.isEmpty {
                EmptyStateView(title: "No events or holidays", symbol: "calendar")
            } else {
                List {
                    ForEach(groupByMonth(events), id: \.0) { group in
                        Section(group.0) {
                            ForEach(group.1) { e in
                                HStack(spacing: 12) {
                                    VStack {
                                        Text(day(e.startDate)).font(.title3.weight(.bold)).foregroundStyle(Theme.brand)
                                        Text(weekday(e.startDate)).font(.caption2).foregroundStyle(.secondary)
                                    }
                                    .frame(width: 44)
                                    VStack(alignment: .leading, spacing: 2) {
                                        Text(e.title).font(.subheadline.weight(.semibold))
                                        if let d = e.description { Text(d).font(.caption).foregroundStyle(.secondary).lineLimit(2) }
                                        HStack {
                                            if let t = e.type { StatusPill(text: t, color: t.lowercased().contains("holiday") ? .purple : Theme.brand) }
                                            if let v = e.venue { Text(v).font(.caption2).foregroundStyle(.secondary) }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                .refreshable { await reload() }
            }
        }
        .navigationTitle("Events & holidays")
        .toolbar {
            if session.can("events", \.update) {
                ToolbarItem(placement: .primaryAction) {
                    NavigationLink(value: Route.module("events", title: "Manage events")) { Image(systemName: "square.and.pencil") }
                }
            }
        }
    }

    private func groupByMonth(_ events: [SchoolEvent]) -> [(String, [SchoolEvent])] {
        let sorted = events.sorted { ($0.startDate ?? "") < ($1.startDate ?? "") }
        var out: [(String, [SchoolEvent])] = []
        for e in sorted {
            let key = DateText.date(e.startDate).map { DateText.monthTitle($0) } ?? "Undated"
            if let i = out.firstIndex(where: { $0.0 == key }) { out[i].1.append(e) } else { out.append((key, [e])) }
        }
        return out
    }

    private func day(_ s: String?) -> String { DateText.date(s).map { String(Calendar.current.component(.day, from: $0)) } ?? "–" }
    private func weekday(_ s: String?) -> String {
        guard let d = DateText.date(s) else { return "" }
        let f = DateFormatter(); f.dateFormat = "EEE"; return f.string(from: d)
    }
}
