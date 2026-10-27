import Foundation

/// Students: which server modules and screens belong to this area (folder Modules/Students/).
public extension ModuleArea {
    static let students = ModuleArea(
        id: "students",
        title: "Students",
        symbol: "graduationcap.fill",
        moduleKeys: ["students", "student_documents"],
        screens: [],
        documents: { key, record in
        guard key == "students" else { return [] }
        let id = record.id
        return [.pdf("id-card", id: id, title: "ID card"),
                .pdf("report-card", id: id, title: "Report card"),
                .pdf("fee-statement", id: id, title: "Fee statement"),
                .pdf("admit-card", id: id, title: "Admit card")]
        }
    )
}
