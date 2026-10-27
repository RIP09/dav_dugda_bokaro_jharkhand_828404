import Foundation

/// Dashboard: which server modules and screens belong to this area (folder Modules/Dashboard/).
public extension ModuleArea {
    static let dashboard = ModuleArea(
        id: "dashboard",
        title: "Dashboard",
        symbol: "square.grid.2x2.fill",
        moduleKeys: [],
        screens: ["dashboard"]
    )
}
