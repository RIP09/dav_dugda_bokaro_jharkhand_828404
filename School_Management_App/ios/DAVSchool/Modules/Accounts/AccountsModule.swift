import Foundation

/// Accounts: which server modules and screens belong to this area (folder Modules/Accounts/).
public extension ModuleArea {
    static let accounts = ModuleArea(
        id: "accounts",
        title: "Accounts",
        symbol: "chart.line.uptrend.xyaxis",
        moduleKeys: ["account_heads", "transactions"],
        screens: []
    )
}
