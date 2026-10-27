import Foundation

/// Attendance: which server modules and screens belong to this area (folder Modules/Attendance/).
public extension ModuleArea {
    static let attendance = ModuleArea(
        id: "attendance",
        title: "Attendance",
        symbol: "checklist",
        moduleKeys: ["student_attendance", "staff_attendance", "leaves"],
        screens: ["attendance_take", "attendance_report", "staff_attendance"]
    )
}
