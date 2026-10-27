import Foundation

/// Timetable: which server modules and screens belong to this area (folder Modules/Timetable/).
public extension ModuleArea {
    static let timetable = ModuleArea(
        id: "timetable",
        title: "Timetable",
        symbol: "calendar.day.timeline.left",
        moduleKeys: ["timetable"],
        screens: ["timetable"]
    )
}
