import SwiftUI

/// Teacher/office marks a class: everyone starts Present, tap to change. Big buttons, works one-handed.
struct TakeAttendanceView: View {
    @EnvironmentObject private var session: SessionStore
    @State private var section: Section?
    @State private var date = Date()
    @State private var sheet: AttendanceSheet?
    @State private var loading = false
    @State private var saving = false
    @State private var banner: Banner?

    var body: some View {
        List {
            Section {
                SectionPicker(selection: $section, onlyMine: session.user?.role == "teacher")
                DatePicker("Date", selection: $date, in: ...Date(), displayedComponents: .date)
            }
            if loading {
                HStack { Spacer(); ProgressView(); Spacer() }
            } else if let sheet = sheet {
                Section {
                    summary(sheet)
                    HStack {
                        Button("All present") { setAll("present") }.buttonStyle(.bordered).tint(Theme.success)
                        Button("All absent") { setAll("absent") }.buttonStyle(.bordered).tint(Theme.danger)
                    }
                    if sheet.alreadyMarked {
                        Label("Already marked for this day – saving will update it (recorded in history).", systemImage: "info.circle")
                            .font(.caption).foregroundStyle(.secondary)
                    }
                }
                Section("Students (\(sheet.rows.count))") {
                    ForEach(sheet.rows.indices, id: \.self) { i in
                        row(i)
                    }
                }
            } else if section != nil {
                Text("No students in this class.").foregroundStyle(.secondary)
            } else {
                Text("Choose a class to start.").foregroundStyle(.secondary)
            }
        }
        .navigationTitle("Take attendance")
        .toolbar {
            ToolbarItem(placement: .confirmationAction) {
                if saving { ProgressView() } else {
                    Button("Save") { Task { await save() } }.fontWeight(.semibold).disabled(sheet?.rows.isEmpty ?? true)
                }
            }
        }
        .task(id: "\(section?.id ?? 0)-\(DateText.isoString(date))") { await load() }
        .banner($banner)
    }

    private func summary(_ s: AttendanceSheet) -> some View {
        let counts = Dictionary(grouping: s.rows, by: \.status).mapValues(\.count)
        return HStack(spacing: 14) {
            ForEach(AttendanceStatus.allCases) { st in
                VStack {
                    Text("\(counts[st.rawValue] ?? 0)").font(.headline).foregroundStyle(Theme.attendanceColor(st.rawValue))
                    Text(st.title).font(.caption2).foregroundStyle(.secondary)
                }
                .frame(maxWidth: .infinity)
            }
        }
    }

    private func row(_ i: Int) -> some View {
        let r = sheet!.rows[i]
        return HStack {
            VStack(alignment: .leading, spacing: 2) {
                Text(r.name).font(.subheadline.weight(.semibold))
                Text("Roll \(r.rollNo.map(String.init) ?? "–")\(r.admissionNo.map { " · \($0)" } ?? "")").font(.caption2).foregroundStyle(.secondary)
            }
            Spacer()
            HStack(spacing: 4) {
                ForEach([AttendanceStatus.present, .absent, .late, .halfDay, .leave]) { st in
                    Button {
                        withAnimation(.spring(response: 0.25)) { sheet!.rows[i].status = st.rawValue }
                    } label: {
                        Text(st.short)
                            .font(.caption.weight(.bold))
                            .frame(width: 30, height: 30)
                            .background(r.status == st.rawValue ? Theme.attendanceColor(st.rawValue) : Color.gray.opacity(0.12), in: Circle())
                            .foregroundStyle(r.status == st.rawValue ? .white : .secondary)
                    }
                    .buttonStyle(.plain)
                }
            }
        }
    }

    private func setAll(_ status: String) {
        guard sheet != nil else { return }
        withAnimation { for i in sheet!.rows.indices { sheet!.rows[i].status = status } }
    }

    private func load() async {
        guard let s = section else { sheet = nil; return }
        loading = true; defer { loading = false }
        do {
            var loaded = try await session.api.attendanceSheet(sectionId: s.id, date: DateText.isoString(date))
            if !loaded.alreadyMarked { for i in loaded.rows.indices where loaded.rows[i].status.isEmpty { loaded.rows[i].status = "present" } }
            sheet = loaded
        } catch {
            sheet = nil
            banner = Banner(text: error.localizedDescription, isError: true)
        }
    }

    private func save() async {
        guard let s = section, let sheet = sheet else { return }
        saving = true; defer { saving = false }
        do {
            let msg = try await session.api.saveAttendance(sectionId: s.id, date: DateText.isoString(date), rows: sheet.rows)
            banner = Banner(text: msg ?? "Attendance saved")
        } catch {
            banner = Banner(text: error.localizedDescription, isError: true)
        }
    }
}

/// Office marks staff attendance for a day.
struct StaffAttendanceView: View {
    @EnvironmentObject private var session: SessionStore
    @State private var date = Date()
    @State private var sheet: StaffSheet?
    @State private var saving = false
    @State private var banner: Banner?
    @State private var error: String?

    var body: some View {
        List {
            Section { DatePicker("Date", selection: $date, in: ...Date(), displayedComponents: .date) }
            if let e = error { Text(e).foregroundStyle(Theme.danger) }
            if let sheet = sheet {
                Section("Staff (\(sheet.rows.count))") {
                    ForEach(sheet.rows.indices, id: \.self) { i in
                        let r = sheet.rows[i]
                        VStack(alignment: .leading, spacing: 6) {
                            HStack {
                                VStack(alignment: .leading) {
                                    Text(r.name).font(.subheadline.weight(.semibold))
                                    if let d = r.designation { Text(d).font(.caption).foregroundStyle(.secondary) }
                                }
                                Spacer()
                                Picker("", selection: Binding(get: { self.sheet!.rows[i].status }, set: { self.sheet!.rows[i].status = $0 })) {
                                    ForEach(AttendanceStatus.allCases) { Text($0.title).tag($0.rawValue) }
                                }
                                .labelsHidden()
                                .tint(Theme.attendanceColor(r.status))
                            }
                            if r.status == "present" || r.status == "late" || r.status == "half_day" {
                                HStack {
                                    TextField("In (08:00)", text: Binding(get: { self.sheet!.rows[i].inTime ?? "" }, set: { self.sheet!.rows[i].inTime = $0 }))
                                    TextField("Out (14:30)", text: Binding(get: { self.sheet!.rows[i].outTime ?? "" }, set: { self.sheet!.rows[i].outTime = $0 }))
                                }
                                .font(.caption).textFieldStyle(.roundedBorder).keyboardType(.numbersAndPunctuation)
                            }
                        }
                    }
                }
            } else if error == nil {
                HStack { Spacer(); ProgressView(); Spacer() }
            }
        }
        .navigationTitle("Staff attendance")
        .toolbar {
            ToolbarItem(placement: .confirmationAction) {
                if saving { ProgressView() } else { Button("Save") { Task { await save() } }.fontWeight(.semibold) }
            }
        }
        .task(id: DateText.isoString(date)) { await load() }
        .banner($banner)
    }

    private func load() async {
        do { sheet = try await session.api.staffSheet(date: DateText.isoString(date)); error = nil }
        catch { self.error = error.localizedDescription }
    }

    private func save() async {
        guard let sheet = sheet else { return }
        saving = true; defer { saving = false }
        do {
            banner = Banner(text: try await session.api.saveStaffAttendance(date: DateText.isoString(date), rows: sheet.rows) ?? "Saved")
        } catch { banner = Banner(text: error.localizedDescription, isError: true) }
    }
}
