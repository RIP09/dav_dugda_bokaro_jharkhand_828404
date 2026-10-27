import Foundation

// MARK: Marks & report cards

public struct MarksSheet: Decodable {
    public let maxMarks: Int
    public let passMarks: Int?
    public let locked: Bool
    public let lockReason: String?
    public var rows: [MarksRow]

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        maxMarks = r.int("max_marks") ?? 100
        passMarks = r.int("pass_marks")
        locked = r.bool("_locked", "locked", "results_locked") ?? false
        lockReason = r.str("_lock_reason", "lock_reason")
        rows = r.list(MarksRow.self, "rows")
    }
}

public struct MarksRow: Decodable, Identifiable, Hashable {
    public var id: Int { studentId }
    public let studentId: Int
    public let name: String
    public let rollNo: Int?
    public var marksObtained: Double?
    public var isAbsent: Bool
    public let grade: String?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        studentId = r.int("student_id") ?? 0
        name = r.str("name") ?? "Student"
        rollNo = r.int("roll_no")
        marksObtained = r.double("marks_obtained")
        isAbsent = r.bool("is_absent") ?? false
        grade = r.str("grade")
    }
}

public struct ReportCard: Decodable {
    public let student: Student?
    public let exam: Exam?
    public let subjects: [ReportSubject]
    public let total: Double
    public let maxTotal: Double
    public let percentage: Double
    public let grade: String?
    public let gpa: Double?
    public let rank: Int?
    public let classStrength: Int?
    public let result: String?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        student = r.decode(Student.self, "student")
        exam = r.decode(Exam.self, "exam")
        subjects = r.list(ReportSubject.self, "subjects")
        total = r.double("total") ?? 0
        maxTotal = r.double("max_total") ?? 0
        percentage = r.double("percentage") ?? 0
        grade = r.str("grade")
        gpa = r.double("gpa")
        rank = r.int("rank")
        classStrength = r.int("class_strength")
        result = r.str("result")
    }
}

public struct ReportSubject: Decodable, Hashable, Identifiable {
    public var id: String { subject }
    public let subject: String
    public let marksObtained: Double?
    public let maxMarks: Double
    public let grade: String?
    public let isAbsent: Bool

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        subject = r.str("subject", "subject_name") ?? "Subject"
        marksObtained = r.double("marks_obtained")
        maxMarks = r.double("max_marks") ?? 0
        grade = r.str("grade")
        isAbsent = r.bool("is_absent") ?? false
    }
}

// MARK: Fees

public struct Invoice: Decodable, Identifiable, Hashable {
    public let id: Int
    public let invoiceNo: String
    public let studentId: Int?
    public let studentName: String?
    public let classLabel: String?
    public let title: String
    public let amount: Double
    public let discount: Double
    public let fine: Double
    public let paid: Double
    public let balance: Double
    public let dueDate: String?
    public let status: String
    public let payments: [Payment]

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        id = r.int("id") ?? 0
        invoiceNo = r.str("invoice_no") ?? "INV-\(id)"
        studentId = r.int("student_id")
        let st = r.decode(Student.self, "student")
        studentName = r.str("student_name") ?? st?.name
        classLabel = r.str("class_label") ?? st?.classLabel
        title = r.str("title") ?? "Fee"
        amount = r.double("amount") ?? 0
        discount = r.double("discount", "concession") ?? 0
        fine = r.double("fine", "late_fine") ?? 0
        paid = r.double("paid") ?? 0
        let bal = r.double("balance")
        balance = bal ?? max(0, (r.double("amount") ?? 0) - (r.double("discount") ?? 0) - (r.double("paid") ?? 0))
        dueDate = r.str("due_date")
        status = r.str("status") ?? "unpaid"
        payments = r.list(Payment.self, "payments")
    }

    public var isPaid: Bool { status == "paid" || balance <= 0.001 }
}

public struct Payment: Decodable, Identifiable, Hashable {
    public let id: Int
    public let receiptNo: String
    public let invoiceId: Int?
    public let amount: Double
    public let method: String
    public let reference: String?
    public let paidOn: String?
    public let studentName: String?
    public let invoiceTitle: String?
    public let status: String?
    public let locked: Bool

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        id = r.int("id") ?? 0
        receiptNo = r.str("receipt_no") ?? "#\(id)"
        invoiceId = r.int("invoice_id")
        amount = r.double("amount") ?? 0
        method = r.str("method") ?? "cash"
        reference = r.str("reference")
        paidOn = r.str("paid_on")
        let inv = r.json("invoice")
        studentName = r.str("student_name") ?? inv?["student"]?["name"]?.stringValue
        invoiceTitle = r.str("invoice_title") ?? inv?["title"]?.stringValue
        status = r.str("status")
        locked = r.bool("_locked") ?? true
    }

    public var isCancelled: Bool { status == "cancelled" }
}

public struct PaymentResult: Decodable {
    public let payment: Payment?
    public let invoice: Invoice?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        payment = r.decode(Payment.self, "payment")
        invoice = r.decode(Invoice.self, "invoice")
    }
}

public struct FeeSummary: Decodable {
    public let billed: Double
    public let collected: Double
    public let due: Double
    public let today: Double
    public let thisMonth: Double
    public let overdueInvoices: Int

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        billed = r.double("billed") ?? 0
        collected = r.double("collected") ?? 0
        due = r.double("due") ?? 0
        today = r.double("today") ?? 0
        thisMonth = r.double("this_month") ?? 0
        overdueInvoices = r.int("overdue_invoices") ?? 0
    }
}

/// One parent from `POST fees/reminders`.
public struct FeeReminder: Decodable, Identifiable, Hashable {
    public var id: String { "\(parentId ?? 0)-\(studentName ?? "")-\(name)" }
    public let parentId: Int?
    public let name: String
    public let phone: String?
    public let studentName: String?
    public let due: Double?
    public let whatsappUrl: String?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        parentId = r.int("parent_id", "id", "user_id")
        name = r.str("parent_name", "name") ?? "Parent"
        phone = r.str("phone")
        studentName = r.str("student_name", "student")
        due = r.double("due", "amount", "balance")
        whatsappUrl = r.str("whatsapp_url")
    }
}
