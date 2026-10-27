import Foundation

/// Staff: which server modules and screens belong to this area (folder Modules/Staff/).
public extension ModuleArea {
    static let staff = ModuleArea(
        id: "staff",
        title: "Staff",
        symbol: "person.3.fill",
        moduleKeys: ["staff"],
        screens: [],
        documents: { key, record in
        let userId = record["user_id"]?.intValue ?? record.id
        return [.pdf("staff-id-card", id: userId, title: "Staff ID card")]
        }
    )
}
