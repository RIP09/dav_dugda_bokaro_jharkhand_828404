import SwiftUI

/// Homework: students submit, parents see, teachers give and check work.
struct HomeworkView: View {
    @EnvironmentObject private var session: SessionStore
    @State private var items: [Homework] = []
    @State private var section: Section?
    @State private var loading = true
    @State private var error: String?
    @State private var showCreate = false

    private var isStaff: Bool { session.isStaff || session.isAdmin }

    var body: some View {
        List {
            if isStaff {
                Section { SectionPicker(selection: $section, title: "Class") }
            }
            if loading && items.isEmpty { HStack { Spacer(); ProgressView(); Spacer() } }
            if let e = error, items.isEmpty { Text(e).foregroundStyle(Theme.danger) }
            if !loading && items.isEmpty && error == nil {
                EmptyStateView(title: "No homework", message: isStaff ? "Tap + to give homework." : "Enjoy! Nothing is due.", symbol: "pencil.and.ruler")
            }
            ForEach(grouped, id: \.0) { group in
                Section(group.0) {
                    ForEach(group.1) { hw in
                        NavigationLink(value: Route.homework(hw)) { HomeworkRow(homework: hw, isStaff: isStaff) }
                    }
                }
            }
        }
        .navigationTitle("Homework")
        .refreshable { await load() }
        .task(id: "\(section?.id ?? 0)-\(session.selectedChild?.id ?? 0)") { await load() }
        .toolbar {
            ToolbarItem(placement: .navigationBarLeading) { ChildSwitcher() }
            if isStaff && session.can("homework", \.create) {
                ToolbarItem(placement: .primaryAction) {
                    Button { showCreate = true } label: { Image(systemName: "plus.circle.fill") }
                }
            }
            if isStaff && session.module("homework") != nil {
                ToolbarItem(placement: .secondaryAction) {
                    NavigationLink(value: Route.module("homework", title: "Manage homework")) { Label("Edit / manage", systemImage: "square.and.pencil") }
                }
            }
        }
        .sheet(isPresented: $showCreate) {
            if let meta = session.module("homework") {
                RecordFormView(meta: meta, existing: nil, presets: section.map { ["section_id": String($0.id), "class_id": String($0.classId ?? 0)] } ?? [:]) { _ in
                    Task { await load() }
                }
            }
        }
    }

    /// Due soon first, then past.
    private var grouped: [(String, [Homework])] {
        let today = DateText.isoString(Date())
        let upcoming = items.filter { ($0.dueDate ?? "9999") >= today }.sorted { ($0.dueDate ?? "") < ($1.dueDate ?? "") }
        let past = items.filter { ($0.dueDate ?? "9999") < today }
        return [("Due", upcoming), ("Earlier", past)].filter { !$0.1.isEmpty }
    }

    private func load() async {
        loading = true; defer { loading = false }
        do {
            items = try await session.api.homework(sectionId: isStaff ? section?.id : nil,
                                                   studentId: session.isParent ? session.currentStudentId : nil)
            error = nil
        } catch { self.error = error.localizedDescription }
    }
}

struct HomeworkRow: View {
    let homework: Homework
    let isStaff: Bool
    var body: some View {
        HStack(alignment: .top, spacing: 12) {
            Image(systemName: "book.closed.fill")
                .foregroundStyle(.white).frame(width: 36, height: 36)
                .background(Theme.brand.gradient, in: RoundedRectangle(cornerRadius: 10))
            VStack(alignment: .leading, spacing: 3) {
                Text(homework.title).font(.subheadline.weight(.semibold)).lineLimit(2)
                Text([homework.subjectName, homework.classLabel, homework.dueDate.map { "Due \(DateText.pretty($0))" }].compactMap { $0 }.joined(separator: " · "))
                    .font(.caption).foregroundStyle(.secondary)
            }
            Spacer()
            if isStaff {
                Text("\(homework.submissionsCount)").font(.caption.weight(.bold)).padding(6).background(Theme.brand.opacity(0.12), in: Circle())
            } else if let s = homework.mySubmission {
                StatusPill(text: s.status)
            }
        }
    }
}

struct HomeworkDetailView: View {
    let homework: Homework
    @EnvironmentObject private var session: SessionStore
    @State private var answer = ""
    @State private var submissions: [HomeworkSubmission] = []
    @State private var sending = false
    @State private var banner: Banner?
    @State private var reviewing: HomeworkSubmission?

    var body: some View {
        List {
            Section {
                Text(homework.title).font(.title3.weight(.bold))
                if let d = homework.description { Text(d) }
                if let s = homework.subjectName { InfoRow(label: "Subject", value: s) }
                if let t = homework.teacherName { InfoRow(label: "Teacher", value: t) }
                if let a = homework.assignedOn { InfoRow(label: "Given on", value: DateText.pretty(a)) }
                if let d = homework.dueDate { InfoRow(label: "Due", value: DateText.pretty(d)) }
            }
            if session.isStudent {
                if let s = homework.mySubmission {
                    Section("Your submission") {
                        Text(s.content ?? "")
                        StatusPill(text: s.status)
                        if let g = s.grade { InfoRow(label: "Grade", value: g) }
                        if let f = s.feedback { InfoRow(label: "Teacher's note", value: f) }
                    }
                }
                Section(homework.mySubmission == nil ? "Submit your work" : "Submit again") {
                    TextField("Write your answer or notes for the teacher", text: $answer, axis: .vertical).lineLimit(4...10)
                    Button { Task { await submit() } } label: { HStack { Text("Submit"); Spacer(); if sending { ProgressView() } } }
                        .disabled(answer.trimmingCharacters(in: .whitespaces).isEmpty || sending)
                }
            } else if session.isParent, let s = homework.mySubmission {
                Section("Submission") {
                    StatusPill(text: s.status)
                    if let g = s.grade { InfoRow(label: "Grade", value: g) }
                    if let f = s.feedback { InfoRow(label: "Teacher's note", value: f) }
                }
            } else if session.isStaff || session.isAdmin {
                Section("Submissions (\(submissions.count))") {
                    if submissions.isEmpty { Text("No submissions yet.").foregroundStyle(.secondary) }
                    ForEach(submissions) { s in
                        Button { reviewing = s } label: {
                            VStack(alignment: .leading, spacing: 4) {
                                HStack { Text(s.studentName ?? "Student").font(.subheadline.weight(.semibold)).foregroundStyle(.primary); Spacer(); StatusPill(text: s.status) }
                                if let c = s.content { Text(c).font(.caption).foregroundStyle(.secondary).lineLimit(3) }
                                if let g = s.grade { Text("Grade: \(g)").font(.caption2) }
                            }
                        }
                    }
                }
            }
        }
        .navigationTitle("Homework")
        .navigationBarTitleDisplayMode(.inline)
        .task { if session.isStaff || session.isAdmin { submissions = (try? await session.api.submissions(homework.id)) ?? [] } }
        .sheet(item: $reviewing) { s in
            ReviewSheet(submission: s) { Task { submissions = (try? await session.api.submissions(homework.id)) ?? [] } }
        }
        .banner($banner)
    }

    private func submit() async {
        sending = true; defer { sending = false }
        do {
            try await session.api.submitHomework(homework.id, content: answer)
            answer = ""
            banner = Banner(text: "Submitted to your teacher")
        } catch { banner = Banner(text: error.localizedDescription, isError: true) }
    }
}

struct ReviewSheet: View {
    let submission: HomeworkSubmission
    let done: () -> Void
    @EnvironmentObject private var session: SessionStore
    @Environment(\.dismiss) private var dismiss
    @State private var status = "reviewed"
    @State private var grade = ""
    @State private var feedback = ""
    @State private var error: String?

    var body: some View {
        NavigationStack {
            Form {
                Section(submission.studentName ?? "Student") { Text(submission.content ?? "—") }
                Section("Check") {
                    Picker("Result", selection: $status) {
                        Text("Checked").tag("reviewed"); Text("Redo").tag("redo")
                    }.pickerStyle(.segmented)
                    TextField("Grade (e.g. A, B+)", text: $grade)
                    TextField("Note for the student", text: $feedback, axis: .vertical)
                }
                if let e = error { Text(e).foregroundStyle(Theme.danger) }
            }
            .navigationTitle("Check work")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) { Button("Cancel") { dismiss() } }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Save") {
                        Task {
                            do {
                                try await session.api.review(submission: submission.id, status: status, grade: grade.isEmpty ? nil : grade, feedback: feedback.isEmpty ? nil : feedback)
                                done(); dismiss()
                            } catch { self.error = error.localizedDescription }
                        }
                    }
                }
            }
            .onAppear { grade = submission.grade ?? ""; feedback = submission.feedback ?? "" }
        }
    }
}
