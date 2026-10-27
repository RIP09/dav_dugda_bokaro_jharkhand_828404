import SwiftUI

/// Make draft salary slips for a month from salary structures, then pay each (paid slips are locked).
struct PayrollRunView: View {
    @EnvironmentObject private var session: SessionStore
    @State private var month = Date()
    @State private var working = false
    @State private var message: String?
    @State private var isError = false

    var body: some View {
        List {
            Section {
                MonthStepper(month: $month)
                Button { Task { await run() } } label: {
                    HStack { Label("Create salary slips for \(DateText.monthTitle(month))", systemImage: "banknote.fill"); Spacer(); if working { ProgressView() } }
                }
                .disabled(working)
            } footer: { Text("Slips are made as drafts from each staff member's salary structure. Check them, then use “Pay” on each slip. Paid slips are locked.") }
            if let m = message {
                Section { Label(m, systemImage: isError ? "exclamationmark.triangle.fill" : "checkmark.circle.fill").foregroundStyle(isError ? Theme.danger : Theme.success) }
            }
            Section {
                NavigationLink(value: Route.module("payslips", title: "Salary slips")) { Label("All salary slips", systemImage: "doc.text.fill") }
                NavigationLink(value: Route.module("salary_structures", title: "Salary structures")) { Label("Salary structures", systemImage: "list.bullet.rectangle") }
            }
        }
        .navigationTitle("Payroll")
    }

    private func run() async {
        working = true; defer { working = false }
        do { message = try await session.api.runPayroll(month: DateText.monthString(month)) ?? "Salary slips created"; isError = false }
        catch { message = error.localizedDescription; isError = true }
    }
}
