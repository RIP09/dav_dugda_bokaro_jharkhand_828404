import Foundation

/// Certificates: which server modules and screens belong to this area (folder Modules/Certificates/).
public extension ModuleArea {
    static let certificates = ModuleArea(
        id: "certificates",
        title: "Certificates",
        symbol: "rosette",
        moduleKeys: ["certificates", "promotions"],
        screens: [],
        documents: { key, record in
        guard key == "certificates" else { return [] }
        return [.pdf("certificate", id: record.id, title: "Print certificate")]
        }
    )
}
