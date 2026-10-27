import Foundation

/// A management area (Students, Fees, Exams …). Each area lives in its own folder under Modules/<Area>/
/// and declares which server modules (`records/{key}`) and custom screens belong to it, plus the
/// printable documents (PDFs) a record of that area offers.
public struct ModuleArea: Identifiable, Hashable {
    public let id: String
    public let title: String
    public let symbol: String
    public let moduleKeys: [String]
    public let screens: [String]
    public let documents: @Sendable (_ moduleKey: String, _ record: Record) -> [ServerDocument]

    public init(id: String, title: String, symbol: String, moduleKeys: [String], screens: [String] = [],
                documents: @escaping @Sendable (_ moduleKey: String, _ record: Record) -> [ServerDocument] = { _, _ in [] }) {
        self.id = id; self.title = title; self.symbol = symbol; self.moduleKeys = moduleKeys
        self.screens = screens; self.documents = documents
    }

    public static func == (a: ModuleArea, b: ModuleArea) -> Bool { a.id == b.id }
    public func hash(into h: inout Hasher) { h.combine(id) }

    /// Every area, in menu order. (Defined in each Modules/<Area>/<Area>Module.swift file.)
    public static var all: [ModuleArea] {
        [.dashboard, .admissions, .students, .parents, .teachers, .staff, .academics, .attendance, .timetable,
         .homework, .exams, .certificates, .fees, .accounts, .payroll, .library, .transport, .hostel,
         .inventory, .frontOffice, .communication, .reports, .settings]
    }

    public static func area(forModule key: String) -> ModuleArea? { all.first { $0.moduleKeys.contains(key) } }
    public static func area(forScreen id: String) -> ModuleArea? { all.first { $0.screens.contains(id) } }

    /// Printable documents for a record of `moduleKey`.
    public static func documents(moduleKey: String, record: Record) -> [ServerDocument] {
        area(forModule: moduleKey)?.documents(moduleKey, record) ?? []
    }
}

/// Built-in menu used only when the server has no `meta/menu` (e.g. a Phase-1 server).
public enum FallbackMenu {
    public static func groups(for role: String, modules: [ModuleMeta]) -> [MenuGroup] {
        // If the registry is there but the menu isn't, group the registry by its `group`.
        if !modules.isEmpty {
            let grouped = Dictionary(grouping: modules) { $0.group ?? "More" }
            var out = [MenuGroup(group: "Home", items: home(role))]
            for g in grouped.keys.sorted() {
                out.append(MenuGroup(group: g, items: grouped[g]!.sorted { $0.title < $1.title }
                    .map { MenuItem(key: $0.key, title: $0.title, icon: $0.icon, screen: $0.key) }))
            }
            return out
        }
        var groups = [MenuGroup(group: "Home", items: home(role))]
        switch role {
        case "admin":
            groups.append(MenuGroup(group: "Daily work", items: [
                MenuItem(key: "attendance_take", title: "Take attendance", icon: "fact_check", screen: "attendance_take"),
                MenuItem(key: "staff_attendance", title: "Staff attendance", icon: "badge", screen: "staff_attendance"),
                MenuItem(key: "attendance_report", title: "Attendance report", icon: "calendar_month", screen: "attendance_report"),
                MenuItem(key: "fee_collect", title: "Collect fees", icon: "payments", screen: "fee_collect"),
                MenuItem(key: "fee_reminders", title: "Fee reminders", icon: "notifications", screen: "fee_reminders"),
                MenuItem(key: "marks_entry", title: "Marks entry", icon: "edit_note", screen: "marks_entry"),
                MenuItem(key: "report_cards", title: "Report cards", icon: "grade", screen: "report_cards"),
                MenuItem(key: "timetable", title: "Timetable", icon: "schedule", screen: "timetable"),
            ]))
        case "teacher", "staff", "librarian", "accountant":
            var items = [
                MenuItem(key: "attendance_take", title: "Take attendance", icon: "fact_check", screen: "attendance_take"),
                MenuItem(key: "marks_entry", title: "Marks entry", icon: "edit_note", screen: "marks_entry"),
                MenuItem(key: "homework", title: "Homework", icon: "assignment", screen: "homework"),
                MenuItem(key: "timetable", title: "My timetable", icon: "schedule", screen: "timetable"),
            ]
            if role == "accountant" { items.insert(MenuItem(key: "fee_collect", title: "Collect fees", icon: "payments", screen: "fee_collect"), at: 0) }
            groups.append(MenuGroup(group: "Daily work", items: items))
        default:
            groups.append(MenuGroup(group: "My school", items: [
                MenuItem(key: "attendance_report", title: "Attendance", icon: "calendar_month", screen: "attendance_report"),
                MenuItem(key: "timetable", title: "Timetable", icon: "schedule", screen: "timetable"),
                MenuItem(key: "homework", title: "Homework", icon: "assignment", screen: "homework"),
                MenuItem(key: "report_cards", title: "Results", icon: "grade", screen: "report_cards"),
                MenuItem(key: "fee_collect", title: "Fees", icon: "payments", screen: "fee_collect"),
            ]))
        }
        groups.append(MenuGroup(group: "Communication", items: [
            MenuItem(key: "notices", title: "Notices", icon: "campaign", screen: "notices"),
            MenuItem(key: "messages", title: "Messages", icon: "chat", screen: "messages"),
            MenuItem(key: "calendar", title: "Events & holidays", icon: "event", screen: "calendar"),
        ]))
        return groups
    }

    static func home(_ role: String) -> [MenuItem] {
        [MenuItem(key: "dashboard", title: "Dashboard", icon: "dashboard", screen: "dashboard"),
         MenuItem(key: "search", title: "Search", icon: "search", screen: "search"),
         MenuItem(key: "notifications", title: "Notifications", icon: "notifications", screen: "notifications")]
    }
}
