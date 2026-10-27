import Foundation

/// Body builder: `["login": .string(x)]` etc. Keys are sent exactly as written (snake_case).
public typealias JSONBody = [String: JSONValue]

/// Every endpoint the iOS apps use, typed. Paths follow docs/API_V2_CONTRACT.md and the Phase-1 README.
public extension APIClient {

    // MARK: Auth
    func login(_ login: String, password: String) async throws -> LoginResponse {
        let body: JSONBody = ["login": .string(login), "password": .string(password),
                              "app": .string(appRole), "device_name": .string("ios")]
        let env: Envelope<LoginResponse> = try await post("auth/login", body: body)
        return env.data
    }
    func me() async throws -> User { try await getData("auth/me") }
    func logout() async { _ = try? await post("auth/logout", as: EmptyResponse.self) }
    func changePassword(current: String, new: String) async throws -> String? {
        let body: JSONBody = ["current_password": .string(current), "new_password": .string(new), "new_password_confirmation": .string(new)]
        let r: MessageOnly = try await post("auth/change-password", body: body)
        return r.message
    }
    func school() async throws -> SchoolProfile { try await getData("school") }

    // MARK: Registry
    func modules() async throws -> [ModuleMeta] { try await getData("meta/modules") }
    func menu() async throws -> [MenuGroup] { try await getData("meta/menu") }

    // MARK: Generic records
    func records(_ module: String, q: String? = nil, page: Int = 1, perPage: Int = 25, sort: String? = "-id",
                 filters: [String: String] = [:]) async throws -> PagedEnvelope<Record> {
        var query: [String: String?] = ["q": q, "page": String(page), "per_page": String(perPage), "sort": sort]
        for (k, v) in filters where !v.isEmpty { query["filter[\(k)]"] = v }
        return try await get("records/\(module)", query: query)
    }
    func record(_ module: String, id: Int) async throws -> Record { try await getData("records/\(module)/\(id)") }
    func createRecord(_ module: String, _ body: JSONBody) async throws -> Record {
        let env: Envelope<Record> = try await post("records/\(module)", body: body)
        return env.data
    }
    func updateRecord(_ module: String, id: Int, _ body: JSONBody) async throws -> Record {
        let env: Envelope<Record> = try await put("records/\(module)/\(id)", body: body)
        return env.data
    }
    func deleteRecord(_ module: String, id: Int) async throws { try await delete("records/\(module)/\(id)") }
    func history(_ module: String, id: Int) async throws -> [AuditEntry] { try await getData("records/\(module)/\(id)/history") }
    /// Runs a module action; returns the server's message.
    func runAction(_ module: String, id: Int, action: String, body: JSONBody = [:]) async throws -> (JSONValue?, String?) {
        let env: Envelope<JSONValue?> = try await post("records/\(module)/\(id)/actions/\(action)", body: body)
        return (env.data, env.message)
    }

    // MARK: Dashboard, search, notifications
    func dashboardCharts(studentId: Int? = nil) async throws -> DashboardCharts {
        try await get("dashboard/charts", query: ["student_id": studentId.map(String.init)])
    }
    func dashboard(studentId: Int? = nil) async throws -> Dashboard {
        try await getData("dashboard", query: ["student_id": studentId.map(String.init)])
    }
    func search(_ q: String) async throws -> [SearchHit] { try await getData("search", query: ["q": q]) }
    func notifications() async throws -> PagedEnvelope<AppNotification> { try await get("notifications") }
    func markNotificationRead(_ id: String) async throws { _ = try await post("notifications/\(id)/read", as: EmptyResponse.self) }
    func markAllNotificationsRead() async throws { _ = try await post("notifications/read-all", as: EmptyResponse.self) }

    // MARK: Academic
    func classes() async throws -> [SchoolClass] { try await getData("classes") }
    func sections(classId: Int? = nil) async throws -> [Section] { try await getData("sections", query: ["class_id": classId.map(String.init)]) }
    func subjects(classId: Int? = nil, mine: Bool = false) async throws -> [Subject] {
        try await getData("subjects", query: ["class_id": classId.map(String.init), "mine": mine ? "1" : nil])
    }
    func timetable(sectionId: Int? = nil, mine: Bool = false, studentId: Int? = nil) async throws -> [TimetableEntry] {
        try await getData("timetable", query: ["section_id": sectionId.map(String.init), "mine": mine ? "1" : nil, "student_id": studentId.map(String.init)])
    }
    func exams() async throws -> [Exam] { try await getData("exams") }
    func examSchedules(examId: Int, classId: Int? = nil, studentId: Int? = nil) async throws -> [ExamSchedule] {
        try await getData("exams/\(examId)/schedules", query: ["class_id": classId.map(String.init), "student_id": studentId.map(String.init)])
    }

    // MARK: Attendance
    func attendanceSheet(sectionId: Int, date: String) async throws -> AttendanceSheet {
        try await getData("attendance/students", query: ["section_id": String(sectionId), "date": date])
    }
    func saveAttendance(sectionId: Int, date: String, rows: [AttendanceSheetRow]) async throws -> String? {
        let records: [JSONValue] = rows.map { .object(["student_id": .number(Double($0.studentId)), "status": .string($0.status),
                                                      "remark": $0.remark.map { .string($0) } ?? .null]) }
        let r: MessageOnly = try await post("attendance/students", body: ["section_id": .number(Double(sectionId)), "date": .string(date), "records": .array(records)] as JSONBody)
        return r.message
    }
    func myAttendance(month: String, studentId: Int? = nil) async throws -> AttendanceReport {
        try await getData("attendance/my", query: ["month": month, "student_id": studentId.map(String.init)])
    }
    func studentAttendance(_ id: Int, month: String) async throws -> AttendanceReport {
        try await getData("students/\(id)/attendance", query: ["month": month])
    }
    func sectionMonthReport(sectionId: Int, month: String) async throws -> SectionMonthReport {
        try await getData("attendance/report", query: ["section_id": String(sectionId), "month": month])
    }
    func classSummary(date: String) async throws -> [SectionAttendance] {
        if let v2: [SectionAttendance] = try? await getData("attendance/daily", query: ["date": date]) { return v2 }
        return try await getData("attendance/class-summary", query: ["date": date])
    }
    func staffSheet(date: String) async throws -> StaffSheet { try await getData("attendance/staff", query: ["date": date]) }
    func saveStaffAttendance(date: String, rows: [StaffSheetRow]) async throws -> String? {
        let records: [JSONValue] = rows.map { .object(["user_id": .number(Double($0.userId)), "status": .string($0.status),
                                                      "in_time": $0.inTime.map { .string($0) } ?? .null,
                                                      "out_time": $0.outTime.map { .string($0) } ?? .null]) }
        let r: MessageOnly = try await post("attendance/staff", body: ["date": .string(date), "records": .array(records)] as JSONBody)
        return r.message
    }

    // MARK: Marks & results
    func marksSheet(examId: Int, sectionId: Int, subjectId: Int) async throws -> MarksSheet {
        try await getData("marks", query: ["exam_id": String(examId), "section_id": String(sectionId), "subject_id": String(subjectId)])
    }
    func saveMarks(examId: Int, sectionId: Int, subjectId: Int, maxMarks: Int, rows: [MarksRow]) async throws -> String? {
        let records: [JSONValue] = rows.map { .object(["student_id": .number(Double($0.studentId)),
                                                      "marks_obtained": $0.marksObtained.map { .number($0) } ?? .null,
                                                      "is_absent": .bool($0.isAbsent)]) }
        let body: JSONBody = ["exam_id": .number(Double(examId)), "section_id": .number(Double(sectionId)),
                              "subject_id": .number(Double(subjectId)), "max_marks": .number(Double(maxMarks)), "records": .array(records)]
        let r: MessageOnly = try await post("marks", body: body)
        return r.message
    }
    func reportCard(studentId: Int, examId: Int?) async throws -> ReportCard {
        try await getData("students/\(studentId)/report-card", query: ["exam_id": examId.map(String.init)])
    }
    func myReportCard(examId: Int?, studentId: Int? = nil) async throws -> ReportCard {
        try await getData("report-card/my", query: ["exam_id": examId.map(String.init), "student_id": studentId.map(String.init)])
    }
    func students(sectionId: Int? = nil, search: String? = nil) async throws -> [Student] {
        try await getData("students", query: ["section_id": sectionId.map(String.init), "search": search])
    }

    // MARK: Fees
    func feeSummary() async throws -> FeeSummary { try await getData("fees/summary") }
    func invoices(status: String? = nil, studentId: Int? = nil, search: String? = nil) async throws -> [Invoice] {
        try await getData("invoices", query: ["status": status, "student_id": studentId.map(String.init), "search": search])
    }
    func collect(invoiceId: Int, amount: Double, method: String, reference: String?) async throws -> PaymentResult {
        let body: JSONBody = ["amount": .number(amount), "method": .string(method), "reference": reference.map { .string($0) } ?? .null]
        let env: Envelope<PaymentResult> = try await post("invoices/\(invoiceId)/payments", body: body)
        return env.data
    }
    func payments() async throws -> [Payment] { try await getData("payments") }
    func sendFeeReminders(classId: Int?, sectionId: Int?) async throws -> ([FeeReminder], String?) {
        var body: JSONBody = [:]
        if let c = classId { body["class_id"] = .number(Double(c)) }
        if let s = sectionId { body["section_id"] = .number(Double(s)) }
        let env: Envelope<[FeeReminder]> = try await post("fees/reminders", body: body)
        return (env.data, env.message)
    }
    func generateInvoices(classId: Int?, month: String) async throws -> String? {
        var body: JSONBody = ["month": .string(month)]
        if let c = classId { body["class_id"] = .number(Double(c)) }
        let env: Envelope<JSONValue?> = try await post("fees/generate", body: body)
        return env.message
    }

    // MARK: Payroll, promotion
    func runPayroll(month: String) async throws -> String? {
        let env: Envelope<JSONValue?> = try await post("payroll/run", body: ["month": .string(month)] as JSONBody)
        return env.message
    }
    func runPromotion(fromYear: Int, toYear: Int, mappings: [(Int, Int)], exclude: [Int]) async throws -> String? {
        let body: JSONBody = [
            "from_academic_year_id": .number(Double(fromYear)), "to_academic_year_id": .number(Double(toYear)),
            "mappings": .array(mappings.map { .object(["from_class_id": .number(Double($0.0)), "to_class_id": .number(Double($0.1))]) }),
            "exclude_student_ids": .array(exclude.map { .number(Double($0)) })]
        let env: Envelope<JSONValue?> = try await post("promotion/run", body: body)
        return env.message
    }

    // MARK: Homework
    func homework(sectionId: Int? = nil, studentId: Int? = nil) async throws -> [Homework] {
        try await getData("homework", query: ["section_id": sectionId.map(String.init), "student_id": studentId.map(String.init)])
    }
    func submitHomework(_ id: Int, content: String) async throws {
        _ = try await post("homework/\(id)/submit", body: ["content": .string(content)] as JSONBody, as: EmptyResponse.self)
    }
    func submissions(_ homeworkId: Int) async throws -> [HomeworkSubmission] { try await getData("homework/\(homeworkId)/submissions") }
    func review(submission id: Int, status: String, grade: String?, feedback: String?) async throws {
        let body: JSONBody = ["status": .string(status), "grade": grade.map { .string($0) } ?? .null, "feedback": feedback.map { .string($0) } ?? .null]
        _ = try await post("submissions/\(id)/review", body: body, as: EmptyResponse.self)
    }

    // MARK: Notices, events, messages, leaves
    func notices() async throws -> [Notice] { try await getData("notices") }
    func markNoticeRead(_ id: Int) async throws { _ = try await post("notices/\(id)/read", as: EmptyResponse.self) }
    func noticeReads(_ id: Int) async throws -> JSONValue { try await getData("notices/\(id)/reads") }
    func events() async throws -> [SchoolEvent] { try await getData("events") }
    func messages(box: String) async throws -> [Message] { try await getData("messages", query: ["box": box]) }
    func contacts() async throws -> [UserLite] { try await getData("messages/contacts") }
    func openMessage(_ id: Int) async throws -> Message { try await getData("messages/\(id)") }
    func sendMessage(to receiverId: Int, subject: String?, body: String) async throws {
        let b: JSONBody = ["receiver_id": .number(Double(receiverId)), "subject": subject.map { .string($0) } ?? .null, "body": .string(body)]
        _ = try await post("messages", body: b, as: EmptyResponse.self)
    }
    func leaves(scope: String? = nil) async throws -> [LeaveRequest] { try await getData("leaves", query: ["scope": scope]) }

    // MARK: Files
    func uploadFile(data: Data, fileName: String, mimeType: String, folder: String? = nil) async throws -> UploadedFile {
        let env: Envelope<UploadedFile> = try await upload("uploads", files: [UploadFile(field: "file", fileName: fileName, mimeType: mimeType, data: data)],
                                                           fields: folder.map { ["folder": $0] } ?? [:])
        return env.data
    }
    func uploadStudentPhoto(studentId: Int, jpeg: Data) async throws {
        _ = try await upload("students/\(studentId)/photo", files: [UploadFile(field: "photo", fileName: "photo.jpg", mimeType: "image/jpeg", data: jpeg)], as: EmptyResponse.self)
    }
    func importFile(module: String, data: Data, fileName: String, dryRun: Bool) async throws -> ImportResult {
        let mime = fileName.lowercased().hasSuffix(".csv") ? "text/csv" : "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
        let env: Envelope<ImportResult> = try await upload("import/\(module)", files: [UploadFile(field: "file", fileName: fileName, mimeType: mime, data: data)],
                                                           fields: dryRun ? ["dry_run": "1"] : [:])
        return env.data
    }
    func audit(module: String? = nil, action: String? = nil, page: Int = 1) async throws -> PagedEnvelope<AuditEntry> {
        try await get("audit", query: ["module": module, "action": action, "page": String(page)])
    }
}

/// A printable/downloadable document on the server.
public struct ServerDocument: Hashable, Identifiable {
    public var id: String { path + query.map { "\($0.key)=\($0.value)" }.sorted().joined() }
    public let title: String
    public let path: String
    public let query: [String: String]

    public init(title: String, path: String, query: [String: String] = [:]) {
        self.title = title; self.path = path; self.query = query
    }

    public static func pdf(_ doc: String, id: Int, title: String, query: [String: String] = [:]) -> ServerDocument {
        ServerDocument(title: title, path: "pdf/\(doc)/\(id)", query: query)
    }
    public static func export(_ module: String, format: String, title: String, filters: [String: String] = [:]) -> ServerDocument {
        var q = ["format": format]
        for (k, v) in filters { q["filter[\(k)]"] = v }
        return ServerDocument(title: title, path: "export/\(module)", query: q)
    }
}
