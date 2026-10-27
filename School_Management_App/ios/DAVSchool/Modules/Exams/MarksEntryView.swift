import SwiftUI

/// Enter marks for one exam + class + subject. Locked exams show the lock and are read-only.
struct MarksEntryView: View {
    @EnvironmentObject private var session: SessionStore
    @State private var exam: Exam?
    @State private var section: Section?
    @State private var subject: Subject?
    @State private var sheet: MarksSheet?
    @State private var loading = false
    @State private var saving = false
    @State private var banner: Banner?
    @FocusState private var focused: Int?

    private var locked: Bool { (sheet?.locked ?? false) || (exam?.resultsLocked ?? false) }

    var body: some View {
        List {
            Section {
                ExamPicker(selection: $exam)
                SectionPicker(selection: $section)
                SubjectPicker(classId: section?.classId, selection: $subject)
            }
            if locked {
                Section { LockBadge(reason: sheet?.lockReason ?? "Results of this exam are locked by the Principal. Marks can't be changed.") }
            }
            if loading {
                HStack { Spacer(); ProgressView(); Spacer() }
            } else if let s = sheet {
                Section("Max marks \(s.maxMarks)\(s.passMarks.map { " · pass \($0)" } ?? "")") {
                    ForEach(s.rows.indices, id: \.self) { i in row(i, max: s.maxMarks) }
                }
            } else if exam != nil && section != nil && subject != nil {
                Text("No students found.").foregroundStyle(.secondary)
            } else {
                Text("Choose exam, class and subject.").foregroundStyle(.secondary)
            }
        }
        .navigationTitle("Marks entry")
        .toolbar {
            ToolbarItem(placement: .confirmationAction) {
                if saving { ProgressView() } else if !locked {
                    Button("Save") { Task { await save() } }.fontWeight(.semibold).disabled(sheet == nil)
                }
            }
            ToolbarItemGroup(placement: .keyboard) {
                Spacer()
                Button("Next") { focused = (focused ?? -1) + 1 }
                Button("Done") { focused = nil }
            }
        }
        .task(id: "\(exam?.id ?? 0)-\(section?.id ?? 0)-\(subject?.id ?? 0)") { await load() }
        .banner($banner)
    }

    private func row(_ i: Int, max: Int) -> some View {
        let r = sheet!.rows[i]
        let over = (r.marksObtained ?? 0) > Double(max)
        return HStack {
            Text(r.rollNo.map(String.init) ?? "–").font(.caption.monospacedDigit()).foregroundStyle(.secondary).frame(width: 26)
            VStack(alignment: .leading) {
                Text(r.name).font(.subheadline)
                if let g = r.grade { Text("Grade \(g)").font(.caption2).foregroundStyle(.secondary) }
            }
            Spacer()
            Toggle("AB", isOn: Binding(get: { sheet!.rows[i].isAbsent }, set: { sheet!.rows[i].isAbsent = $0; if $0 { sheet!.rows[i].marksObtained = nil } }))
                .toggleStyle(.button).font(.caption.weight(.bold)).tint(Theme.danger)
            TextField("–", text: Binding(
                get: { sheet!.rows[i].marksObtained.map { JSONValue.format($0) } ?? "" },
                set: { sheet!.rows[i].marksObtained = Double($0) }))
                .keyboardType(.decimalPad)
                .multilineTextAlignment(.center)
                .frame(width: 60)
                .padding(6)
                .background(over ? Theme.danger.opacity(0.15) : Color.gray.opacity(0.1), in: RoundedRectangle(cornerRadius: 8))
                .focused($focused, equals: i)
                .disabled(r.isAbsent)
        }
        .disabled(locked)
    }

    private func load() async {
        guard let e = exam, let s = section, let sub = subject else { sheet = nil; return }
        loading = true; defer { loading = false }
        do { sheet = try await session.api.marksSheet(examId: e.id, sectionId: s.id, subjectId: sub.id) }
        catch { sheet = nil; banner = Banner(text: error.localizedDescription, isError: true) }
    }

    private func save() async {
        guard let e = exam, let s = section, let sub = subject, let sh = sheet else { return }
        if sh.rows.contains(where: { ($0.marksObtained ?? 0) > Double(sh.maxMarks) }) {
            banner = Banner(text: "Some marks are more than \(sh.maxMarks).", isError: true); return
        }
        saving = true; defer { saving = false }
        do {
            let msg = try await session.api.saveMarks(examId: e.id, sectionId: s.id, subjectId: sub.id, maxMarks: sh.maxMarks, rows: sh.rows)
            banner = Banner(text: msg ?? "Marks saved")
            await load()
        } catch {
            banner = Banner(text: error.localizedDescription, isError: true)
        }
    }
}

/// Office/teacher: pick a class and student to see the report card (PDF too).
struct ReportCardsView: View {
    @EnvironmentObject private var session: SessionStore
    @State private var section: Section?
    @State private var students: [Student] = []

    var body: some View {
        List {
            Section { SectionPicker(selection: $section) }
            if let s = section {
                Section {
                    DocumentButton(doc: ServerDocument(title: "Admit cards of \(s.label)", path: "pdf/admit-cards", query: ["section_id": String(s.id)]),
                                   symbol: "person.text.rectangle")
                }
            }
            Section("Students") {
                ForEach(students) { st in
                    NavigationLink(value: Route.reportCard(studentId: st.id, name: st.name)) {
                        HStack {
                            Avatar(name: st.name, url: st.photoUrl, size: 34)
                            VStack(alignment: .leading) {
                                Text(st.name).font(.subheadline.weight(.semibold))
                                Text("Roll \(st.rollNo.map(String.init) ?? "–") · \(st.admissionNo ?? "")").font(.caption).foregroundStyle(.secondary)
                            }
                        }
                    }
                }
            }
        }
        .navigationTitle("Report cards")
        .task(id: section?.id) {
            guard let s = section else { students = []; return }
            students = (try? await session.api.students(sectionId: s.id)) ?? []
        }
    }
}

/// A student's report card for an exam, CBSE-style, with PDF.
struct ReportCardView: View {
    let studentId: Int?
    let studentName: String?
    @EnvironmentObject private var session: SessionStore
    @State private var exam: Exam?
    @State private var card: ReportCard?
    @State private var error: String?

    private var sid: Int? { studentId ?? session.currentStudentId }

    var body: some View {
        List {
            Section { ExamPicker(selection: $exam, allowAll: true) }
            if let c = card {
                Section {
                    VStack(alignment: .leading, spacing: 6) {
                        Text(c.student?.name ?? studentName ?? "").font(.title3.weight(.bold))
                        Text([c.student?.classLabel, c.exam?.name].compactMap { $0 }.joined(separator: " · ")).font(.subheadline).foregroundStyle(.secondary)
                        HStack(spacing: 16) {
                            big(String(format: "%.1f%%", c.percentage), "Percentage")
                            big(c.grade ?? "–", "Grade")
                            if let r = c.rank { big("\(r)/\(c.classStrength ?? 0)", "Rank") }
                            big(c.result ?? "–", "Result")
                        }
                        .padding(.top, 6)
                    }
                }
                Section("Subjects") {
                    ForEach(c.subjects) { s in
                        HStack {
                            Text(s.subject)
                            Spacer()
                            if s.isAbsent { StatusPill(text: "absent") }
                            else {
                                Text("\(s.marksObtained.map { JSONValue.format($0) } ?? "–")/\(JSONValue.format(s.maxMarks))").monospacedDigit()
                            }
                            Text(s.grade ?? "").font(.caption.weight(.bold)).frame(width: 30).foregroundStyle(Theme.brand)
                        }
                    }
                    HStack {
                        Text("Total").fontWeight(.semibold)
                        Spacer()
                        Text("\(JSONValue.format(c.total))/\(JSONValue.format(c.maxTotal))").fontWeight(.semibold).monospacedDigit()
                    }
                }
                if let id = sid {
                    Section {
                        DocumentButton(doc: .pdf("report-card", id: id, title: "Report card PDF", query: exam.map { ["exam_id": String($0.id)] } ?? (c.exam.map { ["exam_id": String($0.id)] } ?? [:])))
                        DocumentButton(doc: .pdf("admit-card", id: id, title: "Admit card PDF", query: exam.map { ["exam_id": String($0.id)] } ?? [:]), symbol: "person.text.rectangle")
                        Text("Signed by Principal \(session.principalName)").font(.caption).foregroundStyle(.secondary)
                    }
                }
            } else if let e = error {
                Text(e).foregroundStyle(.secondary)
            } else {
                HStack { Spacer(); ProgressView(); Spacer() }
            }
        }
        .navigationTitle(studentName ?? "Results")
        .toolbar { ToolbarItem(placement: .navigationBarLeading) { ChildSwitcher() } }
        .task(id: "\(exam?.id ?? 0)-\(sid ?? 0)") { await load() }
    }

    private func big(_ v: String, _ l: String) -> some View {
        VStack(alignment: .leading) {
            Text(v).font(.headline).foregroundStyle(Theme.brand)
            Text(l).font(.caption2).foregroundStyle(.secondary)
        }
    }

    private func load() async {
        card = nil; error = nil
        do {
            if session.isParent || session.isStudent {
                card = try await session.api.myReportCard(examId: exam?.id, studentId: session.isParent ? sid : nil)
            } else if let id = sid {
                card = try await session.api.reportCard(studentId: id, examId: exam?.id)
            }
        } catch {
            self.error = (error as? APIError).map { e -> String in
                if case .notFound = e { return "Results are not published yet." }
                return e.errorDescription ?? ""
            } ?? error.localizedDescription
        }
    }
}
