import Foundation

/// Exams & Results: which server modules and screens belong to this area (folder Modules/Exams/).
public extension ModuleArea {
    static let exams = ModuleArea(
        id: "exams",
        title: "Exams & Results",
        symbol: "doc.text.magnifyingglass",
        moduleKeys: ["exams", "exam_schedules", "marks", "grades"],
        screens: ["marks_entry", "report_cards"]
    )
}
