import SwiftUI

/// One student's month as a colour calendar + totals (parents, students, or staff opening a student).
struct AttendanceCalendarView: View {
    let studentId: Int?
    var title = "Attendance"
    @EnvironmentObject private var session: SessionStore
    @State private var month = Date()
    @State private var report: AttendanceReport?
    @State private var error: String?
    private let cols = Array(repeating: GridItem(.flexible(), spacing: 6), count: 7)

    var body: some View {
        ScrollView {
            VStack(spacing: 16) {
                MonthStepper(month: $month).padding(.horizontal)
                if let r = report {
                    ring(r)
                    calendar(r)
                    legend
                } else if let e = error {
                    ErrorStateView(message: e) { await load() }
                } else {
                    ProgressView().padding(40)
                }
            }
            .padding(.vertical)
        }
        .background(Theme.background)
        .navigationTitle(title)
        .toolbar { ToolbarItem(placement: .navigationBarLeading) { ChildSwitcher() } }
        .task(id: "\(DateText.monthString(month))-\(studentId ?? 0)-\(session.selectedChild?.id ?? 0)") { await load() }
    }

    private func ring(_ r: AttendanceReport) -> some View {
        HStack(spacing: 20) {
            ZStack {
                Circle().stroke(Color.gray.opacity(0.15), lineWidth: 12)
                Circle().trim(from: 0, to: CGFloat(min(r.percentage, 100) / 100))
                    .stroke(r.percentage >= 75 ? Theme.success : Theme.danger, style: StrokeStyle(lineWidth: 12, lineCap: .round))
                    .rotationEffect(.degrees(-90))
                    .animation(.easeOut(duration: 0.8), value: r.percentage)
                VStack(spacing: 0) {
                    Text(String(format: "%.0f%%", r.percentage)).font(.title3.weight(.bold))
                    Text("present").font(.caption2).foregroundStyle(.secondary)
                }
            }
            .frame(width: 100, height: 100)
            VStack(alignment: .leading, spacing: 6) {
                stat("Present", r.present, "present")
                stat("Absent", r.absent, "absent")
                stat("Late", r.late, "late")
                stat("Half day", r.halfDay, "half_day")
                stat("Leave", r.leave, "leave")
            }
            Spacer()
        }
        .cardStyle()
        .padding(.horizontal)
    }

    private func stat(_ label: String, _ n: Int, _ status: String) -> some View {
        HStack(spacing: 6) {
            Circle().fill(Theme.attendanceColor(status)).frame(width: 8, height: 8)
            Text("\(label): \(n)").font(.caption)
        }
    }

    private func calendar(_ r: AttendanceReport) -> some View {
        let byDate = Dictionary(r.days.map { ($0.date, $0.status) }, uniquingKeysWith: { a, _ in a })
        let cal = Calendar(identifier: .gregorian)
        let start = cal.date(from: cal.dateComponents([.year, .month], from: month)) ?? month
        let days = cal.range(of: .day, in: .month, for: start)?.count ?? 30
        let lead = (cal.component(.weekday, from: start) + 5) % 7   // Monday first
        return VStack(spacing: 6) {
            HStack { ForEach(["M", "T", "W", "T", "F", "S", "S"].indices, id: \.self) { i in Text(["M", "T", "W", "T", "F", "S", "S"][i]).font(.caption2.weight(.bold)).frame(maxWidth: .infinity).foregroundStyle(.secondary) } }
            LazyVGrid(columns: cols, spacing: 6) {
                ForEach(0..<(lead + days), id: \.self) { i in
                    if i < lead { Color.clear.frame(height: 36) }
                    else {
                        let day = i - lead + 1
                        let date = cal.date(byAdding: .day, value: day - 1, to: start) ?? start
                        let status = byDate[DateText.isoString(date)]
                        Text("\(day)")
                            .font(.caption.weight(.semibold))
                            .frame(maxWidth: .infinity, minHeight: 36)
                            .background(status.map { Theme.attendanceColor($0).opacity(0.85) } ?? Color.gray.opacity(0.08), in: RoundedRectangle(cornerRadius: 8))
                            .foregroundStyle(status == nil ? Color.primary : Color.white)
                    }
                }
            }
        }
        .cardStyle()
        .padding(.horizontal)
    }

    private var legend: some View {
        HStack(spacing: 12) {
            ForEach(AttendanceStatus.allCases) { s in
                HStack(spacing: 4) { Circle().fill(Theme.attendanceColor(s.rawValue)).frame(width: 8, height: 8); Text(s.title).font(.caption2) }
            }
        }
        .foregroundStyle(.secondary)
    }

    private func load() async {
        report = nil
        let m = DateText.monthString(month)
        do {
            if let id = studentId ?? session.currentStudentId, !(session.isStudent || session.isParent) {
                report = try await session.api.studentAttendance(id, month: m)
            } else {
                report = try await session.api.myAttendance(month: m, studentId: session.isParent ? session.currentStudentId : nil)
            }
            error = nil
        } catch { self.error = error.localizedDescription }
    }
}

/// Office/teacher view: a section's month (day by day), today's per-class counts, PDF/Excel register.
struct AttendanceReportView: View {
    @EnvironmentObject private var session: SessionStore
    @StateObject private var docs = DocumentLoader()
    @State private var mode = 0
    @State private var section: Section?
    @State private var month = Date()
    @State private var date = Date()
    @State private var report: SectionMonthReport?
    @State private var daily: [SectionAttendance] = []
    @State private var error: String?

    var body: some View {
        List {
            Picker("", selection: $mode) { Text("Monthly").tag(0); Text("Today by class").tag(1) }
                .pickerStyle(.segmented).listRowBackground(Color.clear)
            if mode == 0 { monthly } else { dailyView }
            if let e = error { Text(e).foregroundStyle(Theme.danger).font(.footnote) }
        }
        .navigationTitle("Attendance report")
        .task(id: "\(mode)-\(section?.id ?? 0)-\(DateText.monthString(month))-\(DateText.isoString(date))") { await load() }
        .sheet(item: $docs.preview) { PreviewSheet(file: $0) }
    }

    @ViewBuilder private var monthly: some View {
        Section {
            SectionPicker(selection: $section)
            MonthStepper(month: $month)
            if let s = section {
                Button { docs.open(.pdf("attendance", id: s.id, title: "Attendance register \(s.label)", query: ["month": DateText.monthString(month)]), api: session.api) } label: {
                    Label("Download register (PDF)", systemImage: "doc.richtext")
                }
                Button { docs.open(.export("student_attendance", format: "xlsx", title: "Attendance \(s.label)", filters: ["section_id": String(s.id), "month": DateText.monthString(month)]), api: session.api) } label: {
                    Label("Download Excel", systemImage: "tablecells")
                }
            }
        }
        if let r = report {
            Section("Students") {
                ForEach(r.students) { st in
                    NavigationLink(value: Route.studentAttendance(studentId: st.studentId, name: st.name)) {
                        HStack {
                            VStack(alignment: .leading) {
                                Text(st.name).font(.subheadline.weight(.semibold))
                                Text("Present \(st.present ?? st.statuses.values.filter { $0 == "present" }.count) · Absent \(st.absent ?? st.statuses.values.filter { $0 == "absent" }.count)")
                                    .font(.caption).foregroundStyle(.secondary)
                            }
                            Spacer()
                            if let p = st.percentage {
                                Text(String(format: "%.0f%%", p)).font(.subheadline.weight(.bold)).foregroundStyle(p >= 75 ? Theme.success : Theme.danger)
                            }
                        }
                    }
                }
            }
        }
    }

    @ViewBuilder private var dailyView: some View {
        Section { DatePicker("Date", selection: $date, in: ...Date(), displayedComponents: .date) }
        Section("Classes") {
            if daily.isEmpty { Text("No attendance data for this day.").foregroundStyle(.secondary) }
            ForEach(daily) { d in
                HStack {
                    Text(d.label).font(.subheadline.weight(.semibold))
                    Spacer()
                    if d.marked {
                        Text("\(d.present)/\(d.total)").font(.subheadline.monospacedDigit())
                        StatusPill(text: "marked", color: Theme.success)
                    } else {
                        StatusPill(text: "not marked", color: Theme.warning)
                    }
                }
            }
        }
    }

    private func load() async {
        error = nil
        do {
            if mode == 0 {
                guard let s = section else { report = nil; return }
                report = try await session.api.sectionMonthReport(sectionId: s.id, month: DateText.monthString(month))
            } else {
                daily = try await session.api.classSummary(date: DateText.isoString(date))
            }
        } catch { self.error = error.localizedDescription }
    }
}
