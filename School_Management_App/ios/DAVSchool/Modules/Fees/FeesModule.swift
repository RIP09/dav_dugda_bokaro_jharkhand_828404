import Foundation

/// Fees: which server modules and screens belong to this area (folder Modules/Fees/).
public extension ModuleArea {
    static let fees = ModuleArea(
        id: "fees",
        title: "Fees",
        symbol: "indianrupeesign.circle.fill",
        moduleKeys: ["fee_types", "fee_structures", "concessions", "invoices", "payments"],
        screens: ["fee_collect", "fee_reminders"],
        documents: { key, record in
        switch key {
        case "invoices": return [.pdf("invoice", id: record.id, title: "Print invoice")]
        case "payments": return [.pdf("receipt", id: record.id, title: "Print receipt")]
        default: return []
        }
        }
    )
}
