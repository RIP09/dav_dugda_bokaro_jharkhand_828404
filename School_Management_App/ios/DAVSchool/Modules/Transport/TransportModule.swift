import Foundation

/// Transport: which server modules and screens belong to this area (folder Modules/Transport/).
public extension ModuleArea {
    static let transport = ModuleArea(
        id: "transport",
        title: "Transport",
        symbol: "bus.fill",
        moduleKeys: ["transport_routes", "vehicles"],
        screens: []
    )
}
