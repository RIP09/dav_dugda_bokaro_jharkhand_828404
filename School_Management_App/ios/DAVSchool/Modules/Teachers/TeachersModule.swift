import Foundation

/// Teachers: which server modules and screens belong to this area (folder Modules/Teachers/).
public extension ModuleArea {
    static let teachers = ModuleArea(
        id: "teachers",
        title: "Teachers",
        symbol: "person.crop.rectangle.stack.fill",
        moduleKeys: ["teachers"],
        screens: [],
        documents: { key, record in
        let userId = record["user_id"]?.intValue ?? record.id
        return [.pdf("staff-id-card", id: userId, title: "Staff ID card")]
        }
    )
}
