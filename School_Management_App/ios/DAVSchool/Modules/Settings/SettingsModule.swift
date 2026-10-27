import Foundation

/// Settings: which server modules and screens belong to this area (folder Modules/Settings/).
public extension ModuleArea {
    static let settings = ModuleArea(
        id: "settings",
        title: "Settings",
        symbol: "gearshape.fill",
        moduleKeys: ["settings"],
        screens: ["settings", "profile"]
    )
}
