package com.davdugda.sms.core.data

import kotlinx.serialization.Serializable

// All JSON keys are snake_case on the wire; the Json instance maps them to camelCase (JsonNamingStrategy.SnakeCase).

@Serializable data class ApiList<T>(val data: List<T> = emptyList(), val message: String? = null)
@Serializable data class ApiItem<T>(val data: T? = null, val message: String? = null)
@Serializable data class ApiMessage(val message: String? = null)
@Serializable data class ApiError(val message: String? = null, val errors: Map<String, List<String>>? = null)

@Serializable data class LoginRequest(val login: String, val password: String, val app: String, val deviceName: String = "android")
@Serializable data class LoginResponse(val token: String, val user: User)

@Serializable
data class User(
    val id: Int,
    val name: String,
    val email: String? = null,
    val phone: String? = null,
    val role: String,
    val gender: String? = null,
    val dob: String? = null,
    val address: String? = null,
    val photoUrl: String? = null,
    val isActive: Boolean = true,
    val lastLoginAt: String? = null,
    val student: Student? = null,
    val staff: StaffProfile? = null,
    val children: List<Student> = emptyList(),
) {
    val roleLabel: String get() = role.replaceFirstChar { it.uppercase() }
    val initials: String get() = name.split(" ").filter { it.isNotBlank() && it[0].isLetter() }.take(2).joinToString("") { it.take(1).uppercase() }
}

@Serializable data class UserLite(val id: Int, val name: String, val role: String? = null, val phone: String? = null, val email: String? = null)

@Serializable
data class StaffProfile(
    val id: Int = 0,
    val employeeId: String? = null,
    val designation: String? = null,
    val department: String? = null,
    val joiningDate: String? = null,
    val qualification: String? = null,
    val salary: Double? = null,
)

@Serializable data class AcademicYear(val id: Int, val title: String, val startDate: String? = null, val endDate: String? = null, val isCurrent: Boolean = false)

@Serializable
data class SchoolClass(
    val id: Int,
    val name: String,
    val numericOrder: Int = 0,
    val group: String? = null,
    val sections: List<Section> = emptyList(),
    val subjects: List<Subject> = emptyList(),
    val studentsCount: Int = 0,
)

@Serializable
data class Section(
    val id: Int,
    val classId: Int,
    val name: String,
    val capacity: Int = 40,
    val teacherId: Int? = null,
    val room: String? = null,
    val teacher: UserLite? = null,
    val schoolClass: SchoolClass? = null,
    val studentsCount: Int? = null,
    /** XI–XII only: "Science" or "Commerce" (no Humanities / Arts). */
    val stream: String? = null,
    /** Server-made label, e.g. "Class 6 – A" (servers from v2.0.1). */
    @kotlinx.serialization.SerialName("label") val serverLabel: String? = null,
    val className: String? = null,
) {
    /** Always says which class the section belongs to: "Class 6 – A", "Class 11 – A (Science)". */
    val label: String get() = serverLabel?.takeIf { it.isNotBlank() } ?: sectionLabel(className ?: schoolClass?.name, name, stream)
    fun labelIn(cls: SchoolClass?): String = serverLabel?.takeIf { it.isNotBlank() } ?: sectionLabel(className ?: schoolClass?.name ?: cls?.name, name, stream)
}

/** "6"/"VI" → "Class 6"/"Class VI"; "Class 6", "LKG", "Nursery" unchanged (same rule as the server). */
fun classDisplayName(name: String?): String {
    val n = name?.trim().orEmpty()
    return if (Regex("^(\\d{1,2}|[IVX]{1,4}|[ivx]{1,4})(st|nd|rd|th)?$").matches(n)) "Class $n" else n
}

/** "Class 6 – A", plus " (Science)" / " (Commerce)" for XI–XII sections. */
fun sectionLabel(className: String?, section: String?, stream: String? = null): String {
    val c = classDisplayName(className); val s = section?.trim().orEmpty()
    val base = when { c.isNotEmpty() && s.isNotEmpty() -> "$c – $s"; c.isNotEmpty() -> c; s.isNotEmpty() -> "Section $s"; else -> "" }
    return if (!stream.isNullOrBlank()) "$base ($stream)" else base
}

@Serializable
data class Subject(
    val id: Int,
    val classId: Int,
    val name: String,
    val code: String? = null,
    val type: String? = null,
    val teacherId: Int? = null,
    val teacher: UserLite? = null,
)

@Serializable
data class Student(
    val id: Int,
    val userId: Int = 0,
    val admissionNo: String = "",
    val rollNo: Int? = null,
    val classId: Int = 0,
    val sectionId: Int = 0,
    val parentId: Int? = null,
    val transportRouteId: Int? = null,
    val admissionDate: String? = null,
    val bloodGroup: String? = null,
    val religion: String? = null,
    val category: String? = null,
    val fatherName: String? = null,
    val motherName: String? = null,
    val guardianPhone: String? = null,
    val house: String? = null,
    val status: String? = null,
    val name: String? = null,
    val classLabel: String? = null,
    val user: UserLite? = null,
    val parent: UserLite? = null,
    val transportRoute: TransportRoute? = null,
    val attendanceSummary: AttendanceShort? = null,
    val feeSummary: FeeShort? = null,
) { val displayName: String get() = name ?: user?.name ?: "Student #$id" }

@Serializable data class AttendanceShort(val present: Int = 0, val absent: Int = 0, val total: Int = 0, val percentage: Double = 0.0)
@Serializable data class FeeShort(val total: Double = 0.0, val paid: Double = 0.0, val due: Double = 0.0)

// ---- Dashboard -------------------------------------------------------------------------------
@Serializable data class Stat(val label: String, val value: String, val icon: String? = null)
@Serializable
data class Dashboard(
    val greeting: String = "",
    val role: String = "",
    val schoolName: String = "",
    val date: String = "",
    val stats: List<Stat> = emptyList(),
    val notices: List<Notice> = emptyList(),
    val events: List<SchoolEvent> = emptyList(),
    val todayClasses: List<TimetableEntry> = emptyList(),
    val children: List<Student> = emptyList(),
    val alerts: List<String> = emptyList(),
    val unreadMessages: Int = 0,
)

// ---- Attendance ------------------------------------------------------------------------------
@Serializable data class AttendanceSheet(val date: String, val sectionId: Int = 0, val alreadyMarked: Boolean = false, val rows: List<AttendanceRow> = emptyList())
@Serializable data class AttendanceRow(val studentId: Int, val name: String? = null, val rollNo: Int? = null, val admissionNo: String? = null, val status: String? = null, val remark: String? = null)
@Serializable data class AttendanceRecord(val studentId: Int, val status: String, val remark: String? = null)
@Serializable data class AttendanceSubmit(val sectionId: Int, val date: String, val records: List<AttendanceRecord>)
@Serializable data class StaffSheet(val date: String, val alreadyMarked: Boolean = false, val rows: List<StaffRow> = emptyList())
@Serializable data class StaffRow(val userId: Int, val name: String, val designation: String? = null, val status: String? = null, val inTime: String? = null, val outTime: String? = null)
@Serializable data class StaffRecord(val userId: Int, val status: String, val inTime: String? = null, val outTime: String? = null)
@Serializable data class StaffSubmit(val date: String, val records: List<StaffRecord>)
@Serializable data class AttendanceDay(val date: String, val status: String, val remark: String? = null)
@Serializable data class StudentRef(val id: Int, val name: String? = null, val classLabel: String? = null)
@Serializable
data class AttendanceReport(
    val month: String = "",
    val present: Int = 0, val absent: Int = 0, val late: Int = 0, val halfDay: Int = 0, val leave: Int = 0,
    val total: Int = 0, val percentage: Double = 0.0,
    val days: List<AttendanceDay> = emptyList(),
    val student: StudentRef? = null,
)
@Serializable data class SectionAttendance(val sectionId: Int, val classLabel: String, val strength: Int = 0, val present: Int = 0, val absent: Int = 0, val marked: Boolean = false)

// ---- Timetable -------------------------------------------------------------------------------
@Serializable
data class TimetableEntry(
    val id: Int,
    val classId: Int = 0, val sectionId: Int = 0, val subjectId: Int = 0, val teacherId: Int? = null,
    val day: Int = 1, val startTime: String = "", val endTime: String = "", val room: String? = null,
    val subjectName: String? = null, val teacherName: String? = null, val classLabel: String? = null, val dayName: String? = null,
)
@Serializable data class TimetableCreate(val sectionId: Int, val subjectId: Int, val teacherId: Int?, val day: Int, val startTime: String, val endTime: String, val room: String? = null)

// ---- Exams -----------------------------------------------------------------------------------
@Serializable data class Exam(val id: Int, val name: String, val term: String? = null, val startDate: String? = null, val endDate: String? = null, val isPublished: Boolean = false)
@Serializable data class ExamCreate(val name: String, val term: String?, val startDate: String?, val endDate: String?)
@Serializable data class ExamSchedule(val id: Int, val examId: Int, val classId: Int, val subjectId: Int, val examDate: String, val startTime: String? = null, val endTime: String? = null, val maxMarks: Int = 100, val passMarks: Int = 33, val subjectName: String? = null)
@Serializable data class Grade(val id: Int, val name: String, val minPercent: Double, val maxPercent: Double, val point: Double = 0.0, val remark: String? = null)
@Serializable data class MarksSheet(val maxMarks: Int = 100, val passMarks: Int = 33, val rows: List<MarkRow> = emptyList())
@Serializable data class MarkRow(val studentId: Int, val name: String? = null, val rollNo: Int? = null, val marksObtained: Double? = null, val isAbsent: Boolean = false, val grade: String? = null, val remark: String? = null)
@Serializable data class MarkEntry(val studentId: Int, val marksObtained: Double?, val isAbsent: Boolean = false)
@Serializable data class MarksSubmit(val examId: Int, val sectionId: Int, val subjectId: Int, val maxMarks: Int, val records: List<MarkEntry>)
@Serializable data class ReportSubject(val subject: String? = null, val marksObtained: Double? = null, val maxMarks: Int = 0, val grade: String? = null, val point: Double? = null, val isAbsent: Boolean = false)
@Serializable
data class ReportCard(
    val student: Student, val exam: Exam, val subjects: List<ReportSubject> = emptyList(),
    val total: Double = 0.0, val maxTotal: Double = 0.0, val percentage: Double = 0.0, val grade: String? = null,
    val gpa: Double? = null, val rank: Int? = null, val classStrength: Int = 0, val result: String? = null,
)

// ---- Fees ------------------------------------------------------------------------------------
@Serializable data class FeeType(val id: Int, val name: String, val description: String? = null, val defaultAmount: Double = 0.0)
@Serializable data class FeeTypeCreate(val name: String, val description: String?, val defaultAmount: Double)
@Serializable
data class Invoice(
    val id: Int, val invoiceNo: String = "", val studentId: Int = 0, val title: String = "",
    val amount: Double = 0.0, val discount: Double = 0.0, val paid: Double = 0.0, val balance: Double = 0.0,
    val dueDate: String? = null, val status: String = "unpaid",
    val student: Student? = null, val feeType: FeeType? = null, val payments: List<Payment> = emptyList(),
)
@Serializable data class Payment(val id: Int, val receiptNo: String = "", val invoiceId: Int = 0, val amount: Double = 0.0, val method: String = "cash", val reference: String? = null, val paidOn: String? = null, val studentName: String? = null, val invoiceTitle: String? = null, val invoiceNo: String? = null)
@Serializable data class InvoiceCreate(val studentId: Int? = null, val classId: Int? = null, val sectionId: Int? = null, val feeTypeId: Int? = null, val title: String, val amount: Double, val discount: Double = 0.0, val dueDate: String? = null)
@Serializable data class PaymentCreate(val amount: Double, val method: String, val reference: String? = null)
@Serializable data class PaymentResult(val payment: Payment, val invoice: Invoice)
@Serializable data class FeeSummary(val billed: Double = 0.0, val collected: Double = 0.0, val due: Double = 0.0, val today: Double = 0.0, val thisMonth: Double = 0.0, val overdueInvoices: Int = 0)
@Serializable data class CountResult(val count: Int = 0)

// ---- Homework --------------------------------------------------------------------------------
@Serializable
data class Homework(
    val id: Int, val classId: Int = 0, val sectionId: Int? = null, val subjectId: Int? = null, val teacherId: Int? = null,
    val title: String, val description: String? = null, val assignedOn: String? = null, val dueDate: String? = null,
    val subjectName: String? = null, val teacherName: String? = null, val classLabel: String? = null,
    val submissionsCount: Int = 0, val mySubmission: HomeworkSubmission? = null,
)
@Serializable data class HomeworkSubmission(val id: Int, val homeworkId: Int = 0, val studentId: Int = 0, val content: String? = null, val submittedAt: String? = null, val status: String = "submitted", val grade: String? = null, val feedback: String? = null, val studentName: String? = null)
@Serializable data class HomeworkCreate(val sectionId: Int, val subjectId: Int?, val title: String, val description: String?, val dueDate: String?)
@Serializable data class SubmitContent(val content: String)
@Serializable data class ReviewSubmission(val status: String, val grade: String?, val feedback: String?)

// ---- Notices & events ------------------------------------------------------------------------
@Serializable data class Notice(val id: Int, val title: String, val body: String = "", val audience: String = "all", val publishDate: String? = null, val isPinned: Boolean = false, val authorName: String? = null,
    val isRead: Boolean? = null, val readAt: String? = null, val readsCount: Int? = null)
@Serializable data class NoticeCreate(val title: String, val body: String, val audience: String, val isPinned: Boolean = false)
@Serializable data class SchoolEvent(val id: Int, val title: String, val description: String? = null, val startDate: String = "", val endDate: String? = null, val location: String? = null, val type: String = "event")
@Serializable data class EventCreate(val title: String, val description: String?, val startDate: String, val endDate: String?, val location: String?, val type: String)

// ---- Library ---------------------------------------------------------------------------------
@Serializable data class Book(val id: Int, val title: String, val author: String? = null, val isbn: String? = null, val category: String? = null, val publisher: String? = null, val totalCopies: Int = 1, val availableCopies: Int = 1, val rack: String? = null)
@Serializable data class BookCreate(val title: String, val author: String?, val isbn: String?, val category: String?, val totalCopies: Int, val rack: String?)
@Serializable data class BookIssue(val id: Int, val bookId: Int = 0, val userId: Int = 0, val issuedOn: String? = null, val dueOn: String? = null, val returnedOn: String? = null, val fine: Double = 0.0, val status: String = "issued", val bookTitle: String? = null, val memberName: String? = null)
@Serializable data class IssueCreate(val bookId: Int, val userId: Int, val dueOn: String? = null)

// ---- Transport -------------------------------------------------------------------------------
@Serializable
data class TransportRoute(
    val id: Int, val name: String, val vehicleNo: String? = null, val driverName: String? = null, val driverPhone: String? = null,
    val fare: Double = 0.0, val stops: List<String>? = null, val pickupTime: String? = null, val dropTime: String? = null,
    val studentsCount: Int = 0, val isMine: Boolean = false,
)
@Serializable data class RouteCreate(val name: String, val vehicleNo: String?, val driverName: String?, val driverPhone: String?, val fare: Double, val stops: List<String>)

// ---- Leaves ----------------------------------------------------------------------------------
@Serializable
data class LeaveRequest(
    val id: Int, val userId: Int = 0, val studentId: Int? = null, val type: String = "casual",
    val fromDate: String = "", val toDate: String = "", val reason: String = "", val status: String = "pending",
    val reviewNote: String? = null, val applicantName: String? = null, val studentName: String? = null, val reviewerName: String? = null, val days: Int = 1,
)
@Serializable data class LeaveCreate(val type: String, val fromDate: String, val toDate: String, val reason: String, val studentId: Int? = null)
@Serializable data class LeaveReview(val status: String, val note: String?)

// ---- Messages --------------------------------------------------------------------------------
@Serializable data class Message(val id: Int, val senderId: Int = 0, val receiverId: Int = 0, val subject: String? = null, val body: String = "", val readAt: String? = null, val createdAt: String? = null, val senderName: String? = null, val receiverName: String? = null)
@Serializable data class MessageCreate(val receiverId: Int, val subject: String?, val body: String)

// ---- Admin forms -----------------------------------------------------------------------------
@Serializable
data class StudentCreate(
    val name: String, val classId: Int, val sectionId: Int, val gender: String?, val dob: String?, val phone: String?, val email: String?,
    val fatherName: String?, val motherName: String?, val parentName: String?, val parentPhone: String?, val address: String?,
    val bloodGroup: String?, val transportRouteId: Int? = null,
)
@Serializable data class UserCreate(val name: String, val email: String?, val phone: String?, val role: String, val password: String?, val designation: String?, val department: String?, val gender: String? = null)
@Serializable data class ClassCreate(val name: String, val sections: List<String>, val group: String? = null)
@Serializable data class SectionCreate(val classId: Int, val name: String, val teacherId: Int? = null, val stream: String? = null)
@Serializable data class SubjectCreate(val classId: Int, val name: String, val code: String?, val teacherId: Int?)
@Serializable data class ChangePassword(val currentPassword: String, val newPassword: String)
@Serializable data class Promoted(val promoted: Int = 0)
