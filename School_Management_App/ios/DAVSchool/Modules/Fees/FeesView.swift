import SwiftUI

/// Fees: office sees all invoices & collects; parents/students see their own dues and receipts.
struct FeesView: View {
    @EnvironmentObject private var session: SessionStore
    @State private var status = ""
    @State private var search = ""
    @State private var invoices: [Invoice] = []
    @State private var summary: FeeSummary?
    @State private var loading = true
    @State private var error: String?
    @State private var showGenerate = false
    @State private var banner: Banner?

    private var isOffice: Bool { ["admin", "accountant"].contains(session.user?.role ?? "") }

    var body: some View {
        List {
            if isOffice, let s = summary {
                Section {
                    HStack {
                        tile("Collected", Money.format(s.collected), Theme.success)
                        tile("Due", Money.format(s.due), Theme.danger)
                        tile("Today", Money.format(s.today), Theme.brand)
                    }
                    .listRowInsets(EdgeInsets(top: 8, leading: 8, bottom: 8, trailing: 8))
                }
            } else if !isOffice {
                Section {
                    let due = invoices.reduce(0) { $0 + $1.balance }
                    HStack {
                        tile("Total due", Money.format(due), due > 0 ? Theme.danger : Theme.success)
                        tile("Paid", Money.format(invoices.reduce(0) { $0 + $1.paid }), Theme.success)
                    }
                    if let id = session.currentStudentId {
                        DocumentButton(doc: .pdf("fee-statement", id: id, title: "Fee statement (PDF)"))
                    }
                    Text("Pay at the school office. Every payment gets a receipt here instantly.").font(.caption).foregroundStyle(.secondary)
                }
            }
            Section {
                Picker("Show", selection: $status) {
                    Text("All").tag(""); Text("Unpaid").tag("unpaid"); Text("Part paid").tag("partial"); Text("Paid").tag("paid")
                }
                .pickerStyle(.segmented)
            }
            if loading && invoices.isEmpty {
                HStack { Spacer(); ProgressView(); Spacer() }
            } else if let e = error, invoices.isEmpty {
                Text(e).foregroundStyle(Theme.danger)
            } else if invoices.isEmpty {
                Text("No invoices.").foregroundStyle(.secondary)
            }
            ForEach(invoices) { inv in
                NavigationLink(value: Route.invoice(inv)) { InvoiceRow(invoice: inv, showStudent: isOffice) }
            }
        }
        .navigationTitle(isOffice ? "Fees" : "Fees & receipts")
        .searchable(text: $search, prompt: "Student name, admission or invoice no.")
        .onSubmit(of: .search) { Task { await load() } }
        .refreshable { await load() }
        .task(id: "\(status)-\(session.selectedChild?.id ?? 0)") { await load() }
        .toolbar {
            ToolbarItem(placement: .navigationBarLeading) { ChildSwitcher() }
            if isOffice {
                ToolbarItem(placement: .primaryAction) {
                    Menu {
                        NavigationLink(value: Route.screen("fee_reminders", title: "Fee reminders")) { Label("Send due reminders", systemImage: "bell.badge") }
                        Button { showGenerate = true } label: { Label("Generate monthly invoices", systemImage: "doc.badge.plus") }
                        NavigationLink(value: Route.module("payments", title: "Receipts")) { Label("All receipts", systemImage: "doc.plaintext") }
                        NavigationLink(value: Route.module("fee_structures", title: "Fee structure")) { Label("Fee structure", systemImage: "list.bullet.rectangle") }
                        NavigationLink(value: Route.module("concessions", title: "Concessions")) { Label("Concessions", systemImage: "percent") }
                    } label: { Image(systemName: "ellipsis.circle") }
                }
            }
        }
        .sheet(isPresented: $showGenerate) { GenerateInvoicesSheet { banner = Banner(text: $0) } }
        .banner($banner)
    }

    private func tile(_ label: String, _ value: String, _ color: Color) -> some View {
        VStack(alignment: .leading, spacing: 2) {
            Text(value).font(.headline).foregroundStyle(color).lineLimit(1).minimumScaleFactor(0.6)
            Text(label).font(.caption2).foregroundStyle(.secondary)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(10)
        .background(color.opacity(0.08), in: RoundedRectangle(cornerRadius: 12))
    }

    private func load() async {
        loading = true; defer { loading = false }
        do {
            let sid = isOffice ? nil : session.currentStudentId
            invoices = try await session.api.invoices(status: status.isEmpty ? nil : status, studentId: sid, search: search.isEmpty ? nil : search)
            if isOffice { summary = try? await session.api.feeSummary() }
            error = nil
        } catch { self.error = error.localizedDescription }
    }
}

struct InvoiceRow: View {
    let invoice: Invoice
    var showStudent = true
    var body: some View {
        HStack {
            VStack(alignment: .leading, spacing: 3) {
                Text(showStudent ? (invoice.studentName ?? invoice.title) : invoice.title).font(.subheadline.weight(.semibold))
                Text([showStudent ? invoice.title : nil, invoice.invoiceNo, invoice.dueDate.map { "Due \(DateText.pretty($0))" }].compactMap { $0 }.joined(separator: " · "))
                    .font(.caption).foregroundStyle(.secondary).lineLimit(2)
            }
            Spacer()
            VStack(alignment: .trailing, spacing: 3) {
                Text(Money.format(invoice.isPaid ? invoice.amount : invoice.balance)).font(.subheadline.weight(.bold))
                StatusPill(text: invoice.status)
            }
        }
    }
}

/// Invoice with its payments; office can collect a payment and print receipts.
struct InvoiceDetailView: View {
    @State var invoice: Invoice
    @EnvironmentObject private var session: SessionStore
    @State private var amount = ""
    @State private var method = "cash"
    @State private var reference = ""
    @State private var collecting = false
    @State private var lastReceipt: Payment?
    @State private var banner: Banner?

    private var isOffice: Bool { ["admin", "accountant"].contains(session.user?.role ?? "") }

    init(invoice: Invoice) { _invoice = State(initialValue: invoice) }

    var body: some View {
        List {
            Section {
                InfoRow(label: "Invoice no.", value: invoice.invoiceNo)
                if let n = invoice.studentName { InfoRow(label: "Student", value: n + (invoice.classLabel.map { " (\($0))" } ?? "")) }
                InfoRow(label: "Fee", value: invoice.title)
                InfoRow(label: "Amount", value: Money.format(invoice.amount))
                if invoice.discount > 0 { InfoRow(label: "Concession", value: "− " + Money.format(invoice.discount)) }
                if invoice.fine > 0 { InfoRow(label: "Late fine", value: Money.format(invoice.fine)) }
                InfoRow(label: "Paid", value: Money.format(invoice.paid))
                HStack { Text("Balance").fontWeight(.semibold); Spacer(); Text(Money.format(invoice.balance)).fontWeight(.bold).foregroundStyle(invoice.balance > 0 ? Theme.danger : Theme.success) }
                if let d = invoice.dueDate { InfoRow(label: "Due date", value: DateText.pretty(d)) }
                DocumentButton(doc: .pdf("invoice", id: invoice.id, title: "Print invoice"), symbol: "printer.fill")
            }

            if isOffice && !invoice.isPaid {
                Section("Collect payment") {
                    LabeledContent("Amount (₹)") {
                        TextField(JSONValue.format(invoice.balance), text: $amount).keyboardType(.decimalPad).multilineTextAlignment(.trailing)
                    }
                    Picker("Paid by", selection: $method) {
                        Text("Cash").tag("cash"); Text("UPI").tag("upi"); Text("Cheque").tag("cheque"); Text("Bank transfer").tag("bank"); Text("Card").tag("card")
                    }
                    if method != "cash" { TextField("UPI / cheque / reference no.", text: $reference) }
                    Button { Task { await collect() } } label: {
                        HStack { Spacer(); if collecting { ProgressView().tint(.white) } else { Text("Collect & issue receipt").fontWeight(.semibold) }; Spacer() }
                    }
                    .listRowBackground(Theme.brand)
                    .foregroundStyle(.white)
                    .disabled(collecting)
                    Label("Receipts are permanent. A wrong receipt can only be cancelled with a reason, then collected again.", systemImage: "lock.fill")
                        .font(.caption2).foregroundStyle(.secondary)
                }
            }

            if let r = lastReceipt {
                Section("Receipt issued") {
                    InfoRow(label: "Receipt no.", value: r.receiptNo)
                    DocumentButton(doc: .pdf("receipt", id: r.id, title: "Open receipt \(r.receiptNo)"), symbol: "doc.plaintext.fill")
                }
            }

            if !invoice.payments.isEmpty {
                Section("Payments") {
                    ForEach(invoice.payments) { p in
                        VStack(alignment: .leading, spacing: 4) {
                            HStack {
                                LockBadge(compact: true)
                                Text(p.receiptNo).font(.subheadline.weight(.semibold))
                                Spacer()
                                Text(Money.format(p.amount)).fontWeight(.semibold).strikethrough(p.isCancelled)
                            }
                            Text("\(p.method.uppercased()) · \(DateText.pretty(p.paidOn))\(p.isCancelled ? " · CANCELLED" : "")").font(.caption).foregroundStyle(.secondary)
                            DocumentButton(doc: .pdf("receipt", id: p.id, title: "Receipt PDF"), symbol: "printer")
                            if isOffice {
                                NavigationLink(value: Route.record("payments", id: p.id)) { Text("Details / cancel").font(.caption) }
                            }
                        }
                    }
                }
            }
        }
        .navigationTitle(invoice.invoiceNo)
        .navigationBarTitleDisplayMode(.inline)
        .task { await refresh() }
        .banner($banner)
    }

    private func refresh() async {
        if let fresh = try? await session.api.invoices(studentId: invoice.studentId).first(where: { $0.id == invoice.id }) {
            invoice = fresh
        }
    }

    private func collect() async {
        let value = Double(amount.replacingOccurrences(of: ",", with: "")) ?? invoice.balance
        guard value > 0 else { banner = Banner(text: "Enter an amount.", isError: true); return }
        guard value <= invoice.balance + 0.01 else { banner = Banner(text: "Amount is more than the balance \(Money.format(invoice.balance)).", isError: true); return }
        collecting = true; defer { collecting = false }
        do {
            let res = try await session.api.collect(invoiceId: invoice.id, amount: value, method: method, reference: reference.isEmpty ? nil : reference)
            if let inv = res.invoice { invoice = inv }
            lastReceipt = res.payment
            amount = ""; reference = ""
            banner = Banner(text: "Payment saved. Receipt \(res.payment?.receiptNo ?? "") issued.")
            await refresh()
        } catch {
            banner = Banner(text: error.localizedDescription, isError: true)
        }
    }
}

struct GenerateInvoicesSheet: View {
    let done: (String) -> Void
    @EnvironmentObject private var session: SessionStore
    @Environment(\.dismiss) private var dismiss
    @State private var month = Date()
    @State private var classId: Int?
    @State private var classes: [SchoolClass] = []
    @State private var working = false
    @State private var error: String?

    var body: some View {
        NavigationStack {
            Form {
                Section {
                    MonthStepper(month: $month)
                    Picker("Class", selection: Binding(get: { classId ?? 0 }, set: { classId = $0 == 0 ? nil : $0 })) {
                        Text("All classes").tag(0)
                        ForEach(classes) { Text($0.name).tag($0.id) }
                    }
                } footer: { Text("Creates invoices from the fee structure, minus concessions, plus transport and hostel fees. Invoices already made for the month are skipped.") }
                if let e = error { Text(e).foregroundStyle(Theme.danger) }
            }
            .navigationTitle("Generate invoices")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) { Button("Cancel") { dismiss() } }
                ToolbarItem(placement: .confirmationAction) {
                    if working { ProgressView() } else { Button("Generate") { Task { await run() } } }
                }
            }
            .task { classes = (try? await session.api.classes()) ?? [] }
        }
        .presentationDetents([.medium])
    }

    private func run() async {
        working = true; defer { working = false }
        do {
            let msg = try await session.api.generateInvoices(classId: classId, month: DateText.monthString(month))
            done(msg ?? "Invoices generated")
            dismiss()
        } catch { self.error = error.localizedDescription }
    }
}

/// Sends in-app due reminders to parents and lists free WhatsApp links for each.
struct FeeRemindersView: View {
    @EnvironmentObject private var session: SessionStore
    @Environment(\.openURL) private var openURL
    @State private var section: Section?
    @State private var results: [FeeReminder] = []
    @State private var message: String?
    @State private var working = false

    var body: some View {
        List {
            Section {
                SectionPicker(selection: $section, title: "Class (optional)")
                Button { Task { await send() } } label: {
                    HStack { Label("Send reminders to parents with dues", systemImage: "bell.badge.fill"); Spacer(); if working { ProgressView() } }
                }
                .disabled(working)
            } footer: { Text("Parents get a notification in their app. You can also send each one a free WhatsApp message below.") }
            if let m = message { Section { Label(m, systemImage: "checkmark.circle.fill").foregroundStyle(Theme.success) } }
            if !results.isEmpty {
                Section("Parents (\(results.count))") {
                    ForEach(results) { r in
                        HStack {
                            VStack(alignment: .leading) {
                                Text(r.name).font(.subheadline.weight(.semibold))
                                Text([r.studentName, r.due.map { "Due \(Money.format($0))" }].compactMap { $0 }.joined(separator: " · ")).font(.caption).foregroundStyle(.secondary)
                            }
                            Spacer()
                            if let w = r.whatsappUrl, let u = URL(string: w) {
                                Button { openURL(u) } label: { Label("WhatsApp", systemImage: "message.fill") }
                                    .buttonStyle(.bordered).tint(.green).font(.caption)
                            }
                        }
                    }
                }
            }
        }
        .navigationTitle("Fee reminders")
    }

    private func send() async {
        working = true; defer { working = false }
        do {
            let (list, msg) = try await session.api.sendFeeReminders(classId: section?.classId, sectionId: section?.id)
            results = list
            message = msg ?? "Reminders sent to \(list.count) parents."
        } catch { message = nil; results = []; self.message = error.localizedDescription }
    }
}
