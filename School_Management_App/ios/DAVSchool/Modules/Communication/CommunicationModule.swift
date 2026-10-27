import Foundation

/// Communication: which server modules and screens belong to this area (folder Modules/Communication/).
public extension ModuleArea {
    static let communication = ModuleArea(
        id: "communication",
        title: "Communication",
        symbol: "megaphone.fill",
        moduleKeys: ["notices", "events", "complaints"],
        screens: ["notices", "messages", "notifications", "calendar"]
    )
}
