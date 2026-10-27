package com.davdugda.sms.core.data

import kotlinx.serialization.KSerializer
import kotlinx.serialization.builtins.MapSerializer
import kotlinx.serialization.builtins.nullable
import kotlinx.serialization.builtins.serializer
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.JsonNamingStrategy
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import java.io.File

/**
 * Decodes real responses captured from the Laravel backend (seeded demo data, every role) with the
 * exact Json configuration the apps use. If the API and the Kotlin models drift apart, this fails.
 * Fixtures: core/src/test/resources/fixtures/<role>__<endpoint>.json
 */
class ApiContractTest {
    private val json = Json {
        ignoreUnknownKeys = true; explicitNulls = false; coerceInputValues = true; isLenient = true
        encodeDefaults = true; namingStrategy = JsonNamingStrategy.SnakeCase
    }

    private fun <T> list(s: KSerializer<T>) = ApiList.serializer(s)
    private fun <T> item(s: KSerializer<T>) = ApiItem.serializer(s)

    /** endpoint part of the fixture file name -> the serializer the app uses for it (first match wins). */
    private val rules: List<Pair<Regex, KSerializer<*>>> = listOf(
        Regex("^login$") to item(LoginResponse.serializer()),
        Regex("^dashboard") to item(Dashboard.serializer()),
        Regex("^auth_me$") to item(User.serializer()),
        Regex("^school$") to item(MapSerializer(String.serializer(), String.serializer().nullable)),
        Regex("^classes$") to list(SchoolClass.serializer()),
        Regex("^sections") to list(Section.serializer()),
        Regex("^subjects") to list(Subject.serializer()),
        Regex("^academic_years") to list(AcademicYear.serializer()),
        Regex("^students_\\d+_report_card") to item(ReportCard.serializer()),
        Regex("^students_\\d+$") to item(Student.serializer()),
        Regex("^students") to list(Student.serializer()),
        Regex("^users") to list(User.serializer()),
        Regex("^attendance_students") to item(AttendanceSheet.serializer()),
        Regex("^attendance_class_summary") to list(SectionAttendance.serializer()),
        Regex("^attendance_staff") to item(StaffSheet.serializer()),
        Regex("^attendance_my") to item(AttendanceReport.serializer()),
        Regex("^timetable") to list(TimetableEntry.serializer()),
        Regex("^exams_\\d+_schedules") to list(ExamSchedule.serializer()),
        Regex("^exams$") to list(Exam.serializer()),
        Regex("^grades") to list(Grade.serializer()),
        Regex("^marks") to item(MarksSheet.serializer()),
        Regex("^report_card_my") to item(ReportCard.serializer()),
        Regex("^fees_summary") to item(FeeSummary.serializer()),
        Regex("^fee_types") to list(FeeType.serializer()),
        Regex("^invoices") to list(Invoice.serializer()),
        Regex("^payments") to list(Payment.serializer()),
        Regex("^homework") to list(Homework.serializer()),
        Regex("^notices") to list(Notice.serializer()),
        Regex("^events") to list(SchoolEvent.serializer()),
        Regex("^library_books") to list(Book.serializer()),
        Regex("^library_issues") to list(BookIssue.serializer()),
        Regex("^transport_routes_\\d+_students") to list(Student.serializer()),
        Regex("^transport_routes") to list(TransportRoute.serializer()),
        Regex("^leaves") to list(LeaveRequest.serializer()),
        Regex("^messages_contacts") to list(UserLite.serializer()),
        Regex("^messages") to list(Message.serializer()),
    )

    private fun fixtures(): List<File> {
        val dir = File(javaClass.classLoader!!.getResource("fixtures")!!.toURI())
        return dir.listFiles()!!.filter { it.extension == "json" }.sortedBy { it.name }
    }

    @Test
    fun everyCapturedResponseDecodes() {
        val files = fixtures()
        assertTrue("fixtures present", files.size > 50)
        val failures = mutableListOf<String>()
        for (f in files) {
            val endpoint = f.nameWithoutExtension.substringAfter("__")
            val ser = rules.firstOrNull { it.first.containsMatchIn(endpoint) }?.second
            if (ser == null) { failures += "${f.name}: no rule"; continue }
            runCatching { json.decodeFromString(ser, f.readText()) }.onFailure { failures += "${f.name}: ${it.message?.take(300)}" }
        }
        assertEquals(failures.joinToString("\n"), 0, failures.size)
    }

    @Test
    fun parentLoginCarriesChildren() {
        val r = json.decodeFromString(item(LoginResponse.serializer()), File(javaClass.classLoader!!.getResource("fixtures/parent__login.json")!!.toURI()).readText())
        val user = r.data!!.user
        assertEquals("parent", user.role)
        assertEquals(2, user.children.size)
        assertTrue(user.children.all { it.classLabel != null })
    }

    @Test
    fun reportCardMath() {
        val rc = json.decodeFromString(item(ReportCard.serializer()), File(javaClass.classLoader!!.getResource("fixtures/student__report_card_my.json")!!.toURI()).readText()).data!!
        assertTrue(rc.subjects.isNotEmpty())
        assertEquals(rc.total, rc.subjects.sumOf { it.marksObtained ?: 0.0 }, 0.01)
    }

    @Test
    fun requestBodiesUseSnakeCase() {
        val body = json.encodeToString(AttendanceSubmit.serializer(), AttendanceSubmit(3, "2026-10-27", listOf(AttendanceRecord(7, "present"))))
        assertTrue(body, body.contains("\"section_id\":3") && body.contains("\"student_id\":7"))
    }

    @Test
    fun serverAddressNormalisation() {
        assertEquals("http://192.168.1.10:8000/api/v1/", SessionStore.normalize("192.168.1.10:8000"))
        assertEquals("https://sms.davdugda.in/api/v1/", SessionStore.normalize("https://sms.davdugda.in/"))
        assertEquals("https://x.in/api/v1/", SessionStore.normalize("https://x.in/api/v1"))
    }
}
