import SwiftUI

/// Weekly timetable: today's day selected first. Teachers see their own periods; office picks a class.
struct TimetableView: View {
    @EnvironmentObject private var session: SessionStore
    @State private var entries: [TimetableEntry] = []
    @State private var day = max(1, min(6, (Calendar.current.component(.weekday, from: Date()) + 5) % 7 + 1))
    @State private var section: Section?
    @State private var error: String?
    @State private var loading = true

    private var isOffice: Bool { session.isAdmin }

    var body: some View {
        List {
            if isOffice { Section { SectionPicker(selection: $section) } }
            Section {
                Picker("Day", selection: $day) {
                    ForEach(1...6, id: \.self) { d in Text(String(TimetableEntry.dayNames[d].prefix(3))).tag(d) }
                }
                .pickerStyle(.segmented)
            }
            let today = entries.filter { $0.day == day }.sorted { ($0.startTime ?? "") < ($1.startTime ?? "") }
            Section(TimetableEntry.dayNames[day]) {
                if loading { HStack { Spacer(); ProgressView(); Spacer() } }
                else if let e = error { Text(e).foregroundStyle(Theme.danger) }
                else if today.isEmpty { Text("No periods.").foregroundStyle(.secondary) }
                ForEach(Array(today.enumerated()), id: \.element.id) { i, p in
                    HStack(spacing: 12) {
                        VStack {
                            Text(p.startTime ?? "").font(.caption.weight(.bold).monospacedDigit())
                            Text(p.endTime ?? "").font(.caption2.monospacedDigit()).foregroundStyle(.secondary)
                        }
                        .frame(width: 54)
                        RoundedRectangle(cornerRadius: 2).fill(palette[i % palette.count]).frame(width: 4)
                        VStack(alignment: .leading, spacing: 2) {
                            Text(p.subjectName ?? "—").font(.subheadline.weight(.semibold))
                            Text([session.isStaff ? p.classLabel : p.teacherName, p.room].compactMap { $0 }.joined(separator: " · "))
                                .font(.caption).foregroundStyle(.secondary)
                        }
                        Spacer()
                    }
                    .padding(.vertical, 2)
                }
            }
        }
        .navigationTitle("Timetable")
        .toolbar {
            ToolbarItem(placement: .navigationBarLeading) { ChildSwitcher() }
            if session.can("timetable", \.update) {
                ToolbarItem(placement: .primaryAction) {
                    NavigationLink(value: Route.module("timetable", title: "Edit timetable", filters: section.map { ["section_id": String($0.id)] } ?? [:])) {
                        Image(systemName: "square.and.pencil")
                    }
                }
            }
        }
        .refreshable { await load() }
        .task(id: "\(section?.id ?? 0)-\(session.selectedChild?.id ?? 0)") { await load() }
    }

    private let palette: [Color] = [Theme.brand, .teal, .orange, .pink, .indigo, .green, .purple, .brown]

    private func load() async {
        loading = true; defer { loading = false }
        do {
            if isOffice {
                guard let s = section else { entries = []; error = nil; return }
                entries = try await session.api.timetable(sectionId: s.id)
            } else if session.isStaff {
                entries = try await session.api.timetable(mine: true)
            } else {
                entries = try await session.api.timetable(studentId: session.isParent ? session.currentStudentId : nil)
            }
            error = nil
        } catch { self.error = error.localizedDescription }
    }
}
