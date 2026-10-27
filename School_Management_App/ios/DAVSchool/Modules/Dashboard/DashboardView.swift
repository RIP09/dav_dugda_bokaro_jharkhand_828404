import SwiftUI
import Charts

struct DashboardView: View {
    @EnvironmentObject private var session: SessionStore
    @State private var charts = DashboardCharts()
    @State private var legacy: Dashboard?
    @State private var loading = true
    @State private var error: String?
    private let grid = [GridItem(.flexible(), spacing: 12), GridItem(.flexible(), spacing: 12)]

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                hero
                if loading && charts.cards.isEmpty && legacy == nil {
                    ProgressView().frame(maxWidth: .infinity).padding(40)
                } else if let e = error, charts.cards.isEmpty, legacy == nil {
                    ErrorStateView(message: e) { await load() }
                } else {
                    cards
                    quickActions
                    ForEach(charts.charts) { ChartCard(chart: $0) }
                    todayClasses
                    noticesSection
                }
            }
            .padding()
        }
        .background(Theme.background)
        .navigationTitle("Home")
        .navigationBarTitleDisplayMode(.inline)
        .toolbar { ToolbarItem(placement: .navigationBarLeading) { ChildSwitcher() } }
        .refreshable { await load() }
        .task(id: session.selectedChild?.id) { await load() }
    }

    // MARK: Sections

    private var hero: some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(legacy?.greeting ?? greeting).font(.title2.weight(.bold))
            Text(session.school?.name ?? "DAV Public School, Dugda").font(.subheadline).opacity(0.9)
            HStack(spacing: 6) {
                Image(systemName: "person.crop.square.filled.and.at.rectangle")
                Text("Principal: \(session.principalName)")
            }
            .font(.caption.weight(.medium)).opacity(0.85)
            if let child = session.selectedChild, session.isParent {
                Text("Showing: \(child.name)\(child.classLabel.map { " · \($0)" } ?? "")")
                    .font(.caption.weight(.semibold)).padding(.top, 4)
            }
            if let year = session.school?.currentSession {
                Text("Session \(year)").font(.caption2).opacity(0.8)
            }
        }
        .foregroundStyle(.white)
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(18)
        .background(Theme.brandGradient, in: RoundedRectangle(cornerRadius: 20, style: .continuous))
        .overlay(alignment: .topTrailing) {
            Image(systemName: "graduationcap.fill").font(.system(size: 60)).foregroundStyle(.white.opacity(0.12)).padding(12)
        }
    }

    private var greeting: String {
        let h = Calendar.current.component(.hour, from: Date())
        let part = h < 12 ? "Good morning" : (h < 17 ? "Good afternoon" : "Good evening")
        let first = session.user?.name.split(separator: " ").first.map(String.init) ?? ""
        return "\(part), \(first)"
    }

    private var allCards: [DashCard] { charts.cards.isEmpty ? (legacy?.stats ?? []) : charts.cards }

    private var cards: some View {
        LazyVGrid(columns: grid, spacing: 12) {
            ForEach(Array(allCards.enumerated()), id: \.offset) { i, c in
                StatCard(title: c.label, value: c.value, symbol: Icons.symbol(c.icon),
                         color: c.color != nil ? Theme.named(c.color) : palette[i % palette.count])
            }
        }
    }

    private let palette: [Color] = [Theme.brand, .teal, .orange, .pink, .indigo, Theme.success]

    @ViewBuilder private var quickActions: some View {
        let actions = quickItems
        if !actions.isEmpty {
            VStack(alignment: .leading, spacing: 10) {
                Text("Quick actions").font(.headline)
                ScrollView(.horizontal, showsIndicators: false) {
                    HStack(spacing: 10) {
                        ForEach(actions, id: \.0) { a in
                            NavigationLink(value: Route.screen(a.0, title: a.1)) {
                                Label(a.1, systemImage: Icons.screenSymbol(a.0))
                                    .font(.subheadline.weight(.medium))
                                    .padding(.horizontal, 14).padding(.vertical, 10)
                                    .background(Theme.brand.opacity(0.12), in: Capsule())
                                    .foregroundStyle(Theme.brand)
                            }
                        }
                    }
                }
            }
        }
    }

    private var quickItems: [(String, String)] {
        switch session.user?.role {
        case "admin": return [("attendance_take", "Attendance"), ("fee_collect", "Collect fee"), ("enquiries", "Enquiries"),
                              ("certificates", "Certificates"), ("fee_reminders", "Fee reminders"), ("audit", "Edit history")]
        case "teacher": return [("attendance_take", "Attendance"), ("marks_entry", "Marks"), ("homework", "Homework"), ("timetable", "Timetable")]
        case "accountant": return [("fee_collect", "Collect fee"), ("fee_reminders", "Reminders"), ("transactions", "Accounts"), ("payslips", "Payslips")]
        case "librarian": return [("book_issues", "Issue / return"), ("books", "Books")]
        case "parent", "student": return [("attendance_report", "Attendance"), ("homework", "Homework"), ("report_cards", "Results"),
                                          ("fee_collect", "Fees"), ("timetable", "Timetable")]
        default: return [("timetable", "Timetable"), ("notices", "Notices")]
        }
    }

    @ViewBuilder private var todayClasses: some View {
        if let classes = legacy?.todayClasses, !classes.isEmpty {
            VStack(alignment: .leading, spacing: 10) {
                Text("Today's classes").font(.headline)
                ForEach(classes) { p in
                    HStack {
                        Text("\(p.startTime ?? "") – \(p.endTime ?? "")").font(.caption.monospacedDigit()).foregroundStyle(.secondary).frame(width: 96, alignment: .leading)
                        VStack(alignment: .leading) {
                            Text(p.subjectName ?? "—").font(.subheadline.weight(.semibold))
                            Text([p.classLabel, p.teacherName, p.room].compactMap { $0 }.joined(separator: " · ")).font(.caption).foregroundStyle(.secondary)
                        }
                        Spacer()
                    }
                }
            }
            .cardStyle()
        }
    }

    @ViewBuilder private var noticesSection: some View {
        if let notices = legacy?.notices, !notices.isEmpty {
            VStack(alignment: .leading, spacing: 10) {
                HStack {
                    Text("Latest notices").font(.headline)
                    Spacer()
                    NavigationLink("See all", value: Route.screen("notices", title: "Notices")).font(.subheadline)
                }
                ForEach(notices.prefix(3)) { n in
                    NavigationLink(value: Route.notice(n)) {
                        HStack(alignment: .top, spacing: 10) {
                            Image(systemName: n.isPinned ? "pin.fill" : "megaphone.fill").foregroundStyle(Theme.accent)
                            VStack(alignment: .leading, spacing: 2) {
                                Text(n.title).font(.subheadline.weight(.semibold)).foregroundStyle(.primary)
                                Text(n.body).font(.caption).foregroundStyle(.secondary).lineLimit(2)
                            }
                            Spacer()
                        }
                    }
                }
            }
            .cardStyle()
        }
    }

    private func load() async {
        loading = true; defer { loading = false }
        let sid = session.currentStudentId
        async let c = try? session.api.dashboardCharts(studentId: sid)
        async let d = try? session.api.dashboard(studentId: sid)
        let (cc, dd) = await (c, d)
        if let cc = cc { withAnimation { charts = cc } }
        if let dd = dd { withAnimation { legacy = dd } }
        error = (cc == nil && dd == nil) ? "Couldn't load the dashboard. Pull down to try again." : nil
    }
}

/// One chart from dashboard/charts (bar, line, pie/doughnut).
struct ChartCard: View {
    let chart: DashChart

    private struct Point: Identifiable {
        let id = UUID()
        let label: String
        let series: String
        let value: Double
    }

    private var points: [Point] {
        chart.datasets.flatMap { ds in
            ds.data.enumerated().map { i, v in Point(label: i < chart.labels.count ? chart.labels[i] : "\(i + 1)", series: ds.label, value: v) }
        }
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            Text(chart.title).font(.headline)
            if points.isEmpty {
                Text("No data yet").font(.caption).foregroundStyle(.secondary)
            } else {
                chartBody.frame(height: 220)
            }
        }
        .cardStyle()
    }

    @ViewBuilder private var chartBody: some View {
        let multi = chart.datasets.count > 1
        switch chart.type {
        case "line":
            Chart(points) { p in
                LineMark(x: .value("Label", p.label), y: .value("Value", p.value))
                    .foregroundStyle(by: .value("Series", p.series))
                    .interpolationMethod(.catmullRom)
                    .symbol(Circle())
                AreaMark(x: .value("Label", p.label), y: .value("Value", p.value))
                    .foregroundStyle(by: .value("Series", p.series))
                    .opacity(multi ? 0 : 0.15)
            }
        case "pie", "doughnut":
            if #available(iOS 17.0, *) {
                Chart(points) { p in
                    SectorMark(angle: .value("Value", p.value), innerRadius: .ratio(chart.type == "doughnut" ? 0.55 : 0), angularInset: 1.5)
                        .foregroundStyle(by: .value("Label", p.label))
                        .cornerRadius(4)
                }
            } else {
                Chart(points) { p in
                    BarMark(x: .value("Value", p.value), y: .value("Label", p.label))
                        .foregroundStyle(by: .value("Label", p.label))
                }
            }
        default:
            Chart(points) { p in
                BarMark(x: .value("Label", p.label), y: .value("Value", p.value))
                    .foregroundStyle(by: .value("Series", p.series))
                    .position(by: .value("Series", p.series))
                    .cornerRadius(4)
            }
        }
    }
}
