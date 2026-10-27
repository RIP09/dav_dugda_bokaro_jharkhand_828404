import SwiftUI

/// New session: move every class up (LKG → UKG … 11 → 12). Promotion history is permanent.
struct PromotionView: View {
    @EnvironmentObject private var session: SessionStore
    @State private var years: [Record] = []
    @State private var classes: [SchoolClass] = []
    @State private var fromYear = 0
    @State private var toYear = 0
    @State private var mapping: [Int: Int] = [:]
    @State private var working = false
    @State private var confirm = false
    @State private var message: String?
    @State private var isError = false

    var body: some View {
        Form {
            Section {
                Picker("From session", selection: $fromYear) { Text("Choose…").tag(0); ForEach(years) { Text($0.title).tag($0.id) } }
                Picker("To session", selection: $toYear) { Text("Choose…").tag(0); ForEach(years) { Text($0.title).tag($0.id) } }
            } footer: { Text("Create the new session first (Classes & Subjects → Academic years).") }
            Section("Move classes") {
                ForEach(classes) { c in
                    Picker(c.name, selection: Binding(get: { mapping[c.id] ?? 0 }, set: { mapping[c.id] = $0 })) {
                        Text("Pass out / don't move").tag(0)
                        ForEach(classes) { Text($0.name).tag($0.id) }
                    }
                }
            }
            Section {
                Button { confirm = true } label: { HStack { Label("Promote students", systemImage: "arrow.up.circle.fill"); Spacer(); if working { ProgressView() } } }
                    .disabled(fromYear == 0 || toYear == 0 || fromYear == toYear || working)
                Label("Promotion records are permanent and can't be edited later.", systemImage: "lock.fill").font(.caption).foregroundStyle(Theme.locked)
            }
            if let m = message { Section { Text(m).foregroundStyle(isError ? Theme.danger : Theme.success) } }
        }
        .navigationTitle("Promote to next class")
        .confirmationDialog("Promote all students now?", isPresented: $confirm, titleVisibility: .visible) {
            Button("Promote") { Task { await run() } }
        } message: { Text("Every student moves to the class you chose. This is saved permanently.") }
        .task {
            years = (try? await session.api.records("academic_years", perPage: 50).data) ?? []
            classes = ((try? await session.api.classes()) ?? []).sorted { ($0.numericOrder ?? 0) < ($1.numericOrder ?? 0) }
            for (i, c) in classes.enumerated() where mapping[c.id] == nil && i + 1 < classes.count { mapping[c.id] = classes[i + 1].id }
        }
    }

    private func run() async {
        working = true; defer { working = false }
        let pairs = mapping.filter { $0.value != 0 }.map { ($0.key, $0.value) }
        do {
            message = try await session.api.runPromotion(fromYear: fromYear, toYear: toYear, mappings: pairs, exclude: []) ?? "Students promoted"
            isError = false
        } catch { message = error.localizedDescription; isError = true }
    }
}
