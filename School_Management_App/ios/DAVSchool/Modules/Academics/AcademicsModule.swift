import Foundation

/// Classes & Subjects: which server modules and screens belong to this area (folder Modules/Academics/).
public extension ModuleArea {
    static let academics = ModuleArea(
        id: "academics",
        title: "Classes & Subjects",
        symbol: "books.vertical.fill",
        moduleKeys: ["academic_years", "classes", "sections", "subjects", "syllabus", "study_materials"],
        screens: ["promotion"],
        documents: { key, record in
        guard key == "sections" else { return [] }
        return [ServerDocument(title: "ID cards of this section", path: "pdf/id-cards", query: ["section_id": String(record.id)]),
                ServerDocument(title: "Attendance register (this month)", path: "pdf/attendance/\(record.id)",
                               query: ["month": DateText.monthString(Date())])]
        }
    )
}
