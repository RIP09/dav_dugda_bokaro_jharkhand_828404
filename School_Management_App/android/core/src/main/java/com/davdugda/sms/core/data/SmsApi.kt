package com.davdugda.sms.core.data

import retrofit2.http.Body
import retrofit2.http.DELETE
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.PUT
import retrofit2.http.Path
import retrofit2.http.Query

/** Retrofit description of the Laravel REST API (backend/routes/api.php). */
interface SmsApi {
    // Auth & profile
    @POST("auth/login") suspend fun login(@Body body: LoginRequest): ApiItem<LoginResponse>
    @GET("auth/me") suspend fun me(): ApiItem<User>
    @POST("auth/logout") suspend fun logout(): ApiMessage
    @POST("auth/change-password") suspend fun changePassword(@Body body: ChangePassword): ApiMessage
    @GET("school") suspend fun school(): ApiItem<Map<String, String?>>
    @GET("dashboard") suspend fun dashboard(@Query("student_id") studentId: Int? = null): ApiItem<Dashboard>

    // Academic structure
    @GET("classes") suspend fun classes(): ApiList<SchoolClass>
    @POST("classes") suspend fun createClass(@Body body: ClassCreate): ApiItem<SchoolClass>
    @DELETE("classes/{id}") suspend fun deleteClass(@Path("id") id: Int): ApiMessage
    @GET("sections") suspend fun sections(@Query("class_id") classId: Int? = null): ApiList<Section>
    @POST("sections") suspend fun createSection(@Body body: SectionCreate): ApiItem<Section>
    @GET("subjects") suspend fun subjects(@Query("class_id") classId: Int? = null, @Query("mine") mine: Int? = null): ApiList<Subject>
    @POST("subjects") suspend fun createSubject(@Body body: SubjectCreate): ApiItem<Subject>
    @DELETE("subjects/{id}") suspend fun deleteSubject(@Path("id") id: Int): ApiMessage
    @GET("academic-years") suspend fun academicYears(): ApiList<AcademicYear>

    // People
    @GET("students") suspend fun students(@Query("class_id") classId: Int? = null, @Query("section_id") sectionId: Int? = null, @Query("search") search: String? = null): ApiList<Student>
    @GET("students/{id}") suspend fun student(@Path("id") id: Int): ApiItem<Student>
    @POST("students") suspend fun createStudent(@Body body: StudentCreate): ApiItem<Student>
    @DELETE("students/{id}") suspend fun deleteStudent(@Path("id") id: Int): ApiMessage
    @GET("users") suspend fun users(@Query("role") role: String? = null, @Query("search") search: String? = null): ApiList<User>
    @POST("users") suspend fun createUser(@Body body: UserCreate): ApiItem<User>
    @POST("users/{id}/toggle-active") suspend fun toggleUser(@Path("id") id: Int): ApiItem<User>

    // Attendance
    @GET("attendance/students") suspend fun attendanceSheet(@Query("section_id") sectionId: Int, @Query("date") date: String): ApiItem<AttendanceSheet>
    @POST("attendance/students") suspend fun saveAttendance(@Body body: AttendanceSubmit): ApiMessage
    @GET("attendance/my") suspend fun myAttendance(@Query("month") month: String? = null, @Query("student_id") studentId: Int? = null): ApiItem<AttendanceReport>
    @GET("students/{id}/attendance") suspend fun studentAttendance(@Path("id") id: Int, @Query("month") month: String? = null): ApiItem<AttendanceReport>
    @GET("attendance/class-summary") suspend fun classAttendanceSummary(@Query("date") date: String? = null): ApiList<SectionAttendance>
    @GET("attendance/staff") suspend fun staffSheet(@Query("date") date: String): ApiItem<StaffSheet>
    @POST("attendance/staff") suspend fun saveStaffAttendance(@Body body: StaffSubmit): ApiMessage

    // Timetable
    @GET("timetable") suspend fun timetable(@Query("section_id") sectionId: Int? = null, @Query("mine") mine: Int? = null, @Query("student_id") studentId: Int? = null): ApiList<TimetableEntry>
    @POST("timetable") suspend fun addPeriod(@Body body: TimetableCreate): ApiItem<TimetableEntry>
    @DELETE("timetable/{id}") suspend fun deletePeriod(@Path("id") id: Int): ApiMessage

    // Exams & results
    @GET("exams") suspend fun exams(): ApiList<Exam>
    @POST("exams") suspend fun createExam(@Body body: ExamCreate): ApiItem<Exam>
    @POST("exams/{id}/publish") suspend fun togglePublish(@Path("id") id: Int): ApiItem<Exam>
    @GET("exams/{id}/schedules") suspend fun examSchedules(@Path("id") id: Int, @Query("class_id") classId: Int? = null, @Query("student_id") studentId: Int? = null): ApiList<ExamSchedule>
    @GET("grades") suspend fun grades(): ApiList<Grade>
    @GET("marks") suspend fun marksSheet(@Query("exam_id") examId: Int, @Query("section_id") sectionId: Int, @Query("subject_id") subjectId: Int): ApiItem<MarksSheet>
    @POST("marks") suspend fun saveMarks(@Body body: MarksSubmit): ApiMessage
    @GET("students/{id}/report-card") suspend fun reportCard(@Path("id") id: Int, @Query("exam_id") examId: Int? = null): ApiItem<ReportCard>
    @GET("report-card/my") suspend fun myReportCard(@Query("exam_id") examId: Int? = null, @Query("student_id") studentId: Int? = null): ApiItem<ReportCard>

    // Fees
    @GET("fees/summary") suspend fun feeSummary(): ApiItem<FeeSummary>
    @GET("fee-types") suspend fun feeTypes(): ApiList<FeeType>
    @POST("fee-types") suspend fun createFeeType(@Body body: FeeTypeCreate): ApiItem<FeeType>
    @GET("invoices") suspend fun invoices(@Query("status") status: String? = null, @Query("student_id") studentId: Int? = null, @Query("class_id") classId: Int? = null, @Query("search") search: String? = null): ApiList<Invoice>
    @POST("invoices") suspend fun createInvoices(@Body body: InvoiceCreate): ApiItem<CountResult>
    @POST("invoices/{id}/payments") suspend fun pay(@Path("id") id: Int, @Body body: PaymentCreate): ApiItem<PaymentResult>
    @GET("payments") suspend fun payments(): ApiList<Payment>

    // Homework
    @GET("homework") suspend fun homework(@Query("section_id") sectionId: Int? = null, @Query("student_id") studentId: Int? = null): ApiList<Homework>
    @POST("homework") suspend fun createHomework(@Body body: HomeworkCreate): ApiItem<Homework>
    @DELETE("homework/{id}") suspend fun deleteHomework(@Path("id") id: Int): ApiMessage
    @POST("homework/{id}/submit") suspend fun submitHomework(@Path("id") id: Int, @Body body: SubmitContent): ApiItem<HomeworkSubmission>
    @GET("homework/{id}/submissions") suspend fun submissions(@Path("id") id: Int): ApiList<HomeworkSubmission>
    @POST("submissions/{id}/review") suspend fun reviewSubmission(@Path("id") id: Int, @Body body: ReviewSubmission): ApiItem<HomeworkSubmission>

    // Notices & events
    @GET("notices") suspend fun notices(): ApiList<Notice>
    @POST("notices") suspend fun createNotice(@Body body: NoticeCreate): ApiItem<Notice>
    @DELETE("notices/{id}") suspend fun deleteNotice(@Path("id") id: Int): ApiMessage
    @GET("events") suspend fun events(): ApiList<SchoolEvent>
    @POST("events") suspend fun createEvent(@Body body: EventCreate): ApiItem<SchoolEvent>
    @DELETE("events/{id}") suspend fun deleteEvent(@Path("id") id: Int): ApiMessage

    // Library
    @GET("library/books") suspend fun books(@Query("search") search: String? = null): ApiList<Book>
    @POST("library/books") suspend fun createBook(@Body body: BookCreate): ApiItem<Book>
    @GET("library/issues") suspend fun issues(@Query("status") status: String? = null): ApiList<BookIssue>
    @POST("library/issues") suspend fun issueBook(@Body body: IssueCreate): ApiItem<BookIssue>
    @POST("library/issues/{id}/return") suspend fun returnBook(@Path("id") id: Int): ApiItem<BookIssue>

    // Transport
    @GET("transport/routes") suspend fun routes(): ApiList<TransportRoute>
    @POST("transport/routes") suspend fun createRoute(@Body body: RouteCreate): ApiItem<TransportRoute>
    @GET("transport/routes/{id}/students") suspend fun routeStudents(@Path("id") id: Int): ApiList<Student>

    // Leaves
    @GET("leaves") suspend fun leaves(@Query("scope") scope: String? = null, @Query("status") status: String? = null): ApiList<LeaveRequest>
    @POST("leaves") suspend fun applyLeave(@Body body: LeaveCreate): ApiItem<LeaveRequest>
    @POST("leaves/{id}/review") suspend fun reviewLeave(@Path("id") id: Int, @Body body: LeaveReview): ApiItem<LeaveRequest>
    @DELETE("leaves/{id}") suspend fun cancelLeave(@Path("id") id: Int): ApiMessage

    // Messages
    @GET("messages") suspend fun messages(@Query("box") box: String): ApiList<Message>
    @GET("messages/contacts") suspend fun contacts(): ApiList<UserLite>
    @POST("messages") suspend fun sendMessage(@Body body: MessageCreate): ApiItem<Message>
    @GET("messages/{id}") suspend fun openMessage(@Path("id") id: Int): ApiItem<Message>
}
