import SwiftUI

/// Everything a NavigationLink can open.
enum Route: Hashable {
    case screen(String, title: String)
    case module(String, title: String? = nil, filters: [String: String] = [:])
    case record(String, id: Int)
    case reportCard(studentId: Int, name: String)
    case studentAttendance(studentId: Int, name: String)
    case homework(Homework)
    case invoice(Invoice)
    case message(Message)
    case notice(Notice)
}

extension View {
    /// Registers every Route destination on a NavigationStack.
    func withRoutes() -> some View {
        navigationDestination(for: Route.self) { route in
            RouteView(route: route)
        }
    }
}

struct RouteView: View {
    let route: Route

    var body: some View {
        switch route {
        case .screen(let id, let title):
            ScreenView(screen: id, title: title)
        case .module(let key, let title, let filters):
            ModuleListView(moduleKey: key, title: title, fixedFilters: filters)
        case .record(let module, let id):
            RecordDetailView(moduleKey: module, recordId: id)
        case .reportCard(let id, let name):
            ReportCardView(studentId: id, studentName: name)
        case .studentAttendance(let id, let name):
            AttendanceCalendarView(studentId: id, title: name)
        case .homework(let hw):
            HomeworkDetailView(homework: hw)
        case .invoice(let inv):
            InvoiceDetailView(invoice: inv)
        case .message(let m):
            MessageDetailView(message: m)
        case .notice(let n):
            NoticeDetailView(notice: n)
        }
    }
}

/// Opens a custom screen id from meta/menu, or the generic engine for a module key.
struct ScreenView: View {
    let screen: String
    let title: String
    @EnvironmentObject private var session: SessionStore

    var body: some View {
        switch screen {
        case "dashboard": DashboardView()
        case "attendance_take": TakeAttendanceView()
        case "attendance_report":
            if session.isParent || session.isStudent { AttendanceCalendarView(studentId: session.currentStudentId, title: "Attendance") }
            else { AttendanceReportView() }
        case "staff_attendance": StaffAttendanceView()
        case "timetable": TimetableView()
        case "marks_entry": MarksEntryView()
        case "report_cards":
            if session.isParent || session.isStudent { ReportCardView(studentId: session.currentStudentId, studentName: nil) }
            else { ReportCardsView() }
        case "fee_collect": FeesView()
        case "fee_reminders": FeeRemindersView()
        case "payroll_run": PayrollRunView()
        case "promotion": PromotionView()
        case "import": DataImportView()
        case "backup": BackupView()
        case "audit": AuditLogView()
        case "settings": SettingsView()
        case "messages": MessagesView()
        case "notifications": NotificationsView()
        case "calendar": CalendarView()
        case "search": SearchView()
        case "profile": ProfileView()
        case "notices": NoticesView()
        case "homework": HomeworkView()
        default: ModuleListView(moduleKey: screen, title: title)
        }
    }
}
