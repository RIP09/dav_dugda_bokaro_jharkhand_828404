import Foundation

/// Inventory & Assets: which server modules and screens belong to this area (folder Modules/Inventory/).
public extension ModuleArea {
    static let inventory = ModuleArea(
        id: "inventory",
        title: "Inventory & Assets",
        symbol: "shippingbox.fill",
        moduleKeys: ["inventory_items", "stock_movements", "assets"],
        screens: []
    )
}
