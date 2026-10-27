import Foundation

/// Front Office: which server modules and screens belong to this area (folder Modules/FrontOffice/).
public extension ModuleArea {
    static let frontOffice = ModuleArea(
        id: "frontOffice",
        title: "Front Office",
        symbol: "person.text.rectangle.fill",
        moduleKeys: ["visitors", "postal", "call_logs"],
        screens: []
    )
}
