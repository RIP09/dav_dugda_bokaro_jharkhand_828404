import Foundation

/// Admissions: which server modules and screens belong to this area (folder Modules/Admissions/).
public extension ModuleArea {
    static let admissions = ModuleArea(
        id: "admissions",
        title: "Admissions",
        symbol: "person.badge.plus",
        moduleKeys: ["enquiries", "admissions"],
        screens: []
    )
}
