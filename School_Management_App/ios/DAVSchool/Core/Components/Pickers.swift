import SwiftUI

/// Picks a class-section ("Class 5 - A"). Loads sections once.
struct SectionPicker: View {
    @Binding var selection: Section?
    var title = "Class"
    /// Teachers: show only the sections where they are class teacher (falls back to all if none).
    var onlyMine = false
    @EnvironmentObject private var session: SessionStore
    @State private var sections: [Section] = []

    var body: some View {
        Picker(title, selection: Binding(get: { selection?.id ?? 0 }, set: { id in selection = sections.first { $0.id == id } })) {
            Text("Choose…").tag(0)
            ForEach(sections) { Text($0.label).tag($0.id) }
        }
        .task {
            guard sections.isEmpty else { return }
            let all = (try? await session.api.sections()) ?? []
            let mine = all.filter { $0.teacherId != nil && $0.teacherId == session.user?.id }
            sections = (onlyMine && !mine.isEmpty) ? mine : all
            if sections.count == 1 { selection = sections.first }
        }
    }
}

struct SubjectPicker: View {
    let classId: Int?
    @Binding var selection: Subject?
    @EnvironmentObject private var session: SessionStore
    @State private var subjects: [Subject] = []

    var body: some View {
        Picker("Subject", selection: Binding(get: { selection?.id ?? 0 }, set: { id in selection = subjects.first { $0.id == id } })) {
            Text("Choose…").tag(0)
            ForEach(subjects) { Text($0.name).tag($0.id) }
        }
        .task(id: classId) {
            selection = nil
            guard let c = classId else { subjects = []; return }
            let mine = session.user?.role == "teacher"
            var list = (try? await session.api.subjects(classId: c, mine: mine)) ?? []
            if list.isEmpty && mine { list = (try? await session.api.subjects(classId: c)) ?? [] }
            subjects = list
        }
    }
}

struct ExamPicker: View {
    @Binding var selection: Exam?
    var allowAll = false
    @EnvironmentObject private var session: SessionStore
    @State private var exams: [Exam] = []

    var body: some View {
        Picker("Exam", selection: Binding(get: { selection?.id ?? 0 }, set: { id in selection = exams.first { $0.id == id } })) {
            Text(allowAll ? "Latest" : "Choose…").tag(0)
            ForEach(exams) { e in
                Text(e.resultsLocked ? "🔒 \(e.name)" : e.name).tag(e.id)
            }
        }
        .task {
            guard exams.isEmpty else { return }
            exams = (try? await session.api.exams()) ?? []
        }
    }
}

/// Month stepper: ‹ October 2026 ›
struct MonthStepper: View {
    @Binding var month: Date

    var body: some View {
        HStack {
            Button { shift(-1) } label: { Image(systemName: "chevron.left.circle.fill").font(.title2) }
            Spacer()
            Text(DateText.monthTitle(month)).font(.headline)
            Spacer()
            Button { shift(1) } label: { Image(systemName: "chevron.right.circle.fill").font(.title2) }
        }
        .foregroundStyle(Theme.brand)
    }

    private func shift(_ by: Int) {
        if let d = Calendar.current.date(byAdding: .month, value: by, to: month) { month = d }
    }
}

/// Parents with more than one child switch between them here; every parent screen follows the choice.
struct ChildSwitcher: View {
    @EnvironmentObject private var session: SessionStore

    var body: some View {
        if session.isParent, let children = session.user?.children, children.count > 1 {
            Menu {
                ForEach(children) { child in
                    Button {
                        session.selectedChild = child
                    } label: {
                        if child.id == session.selectedChild?.id { Label(child.name, systemImage: "checkmark") } else { Text(child.name) }
                    }
                }
            } label: {
                HStack(spacing: 6) {
                    Avatar(name: session.selectedChild?.name ?? "", url: session.selectedChild?.photoUrl, size: 26)
                    Text(session.selectedChild?.name.split(separator: " ").first.map(String.init) ?? "Child").font(.subheadline.weight(.semibold))
                    Image(systemName: "chevron.down").font(.caption2)
                }
            }
        }
    }
}
