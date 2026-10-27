import Foundation

/// Reports & Data: which server modules and screens belong to this area (folder Modules/Reports/).
public extension ModuleArea {
    static let reports = ModuleArea(
        id: "reports",
        title: "Reports & Data",
        symbol: "chart.bar.doc.horizontal.fill",
        moduleKeys: ["audit"],
        screens: ["audit", "import", "backup", "search"]
    )
}
