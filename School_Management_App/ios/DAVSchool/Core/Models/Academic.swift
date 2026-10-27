import Foundation

public struct SchoolClass: Decodable, Identifiable, Hashable {
    public let id: Int
    public let name: String
    public let numericOrder: Int?
    public let sections: [Section]

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        id = r.int("id") ?? 0
        name = r.str("name") ?? "Class"
        numericOrder = r.int("numeric_order")
        sections = r.list(Section.self, "sections")
    }
}

public struct Section: Decodable, Identifiable, Hashable {
    public let id: Int
    public let classId: Int?
    public let name: String
    public let className: String?
    public let studentsCount: Int?
    /// Class teacher (user id) – teachers take attendance only for their own sections.
    public let teacherId: Int?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        id = r.int("id") ?? 0
        teacherId = r.int("teacher_id")
        classId = r.int("class_id")
        name = r.str("name") ?? ""
        className = r.json("school_class")?["name"]?.stringValue ?? r.str("class_name")
        studentsCount = r.int("students_count")
        _label = r.str("label", "class_label")
    }
    private let _label: String?

    /// "Class 5 - A"
    public var label: String { _label ?? ([className, name].compactMap { $0 }.joined(separator: " - ")) }
}

public struct Subject: Decodable, Identifiable, Hashable {
    public let id: Int
    public let classId: Int?
    public let name: String
    public let code: String?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        id = r.int("id") ?? 0
        classId = r.int("class_id")
        name = r.str("name") ?? "Subject"
        code = r.str("code")
    }
}

public struct Exam: Decodable, Identifiable, Hashable {
    public let id: Int
    public let name: String
    public let term: String?
    public let startDate: String?
    public let endDate: String?
    public let isPublished: Bool
    public let resultsLocked: Bool

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        id = r.int("id") ?? 0
        name = r.str("name") ?? "Exam"
        term = r.str("term")
        startDate = r.str("start_date")
        endDate = r.str("end_date")
        isPublished = r.bool("is_published") ?? false
        resultsLocked = r.bool("results_locked", "is_locked", "_locked") ?? false
    }
}

public struct TimetableEntry: Decodable, Identifiable, Hashable {
    public let id: Int
    public let day: Int
    public let dayName: String?
    public let startTime: String?
    public let endTime: String?
    public let subjectName: String?
    public let teacherName: String?
    public let classLabel: String?
    public let room: String?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        id = r.int("id") ?? 0
        day = r.int("day") ?? 1
        dayName = r.str("day_name")
        startTime = r.str("start_time")
        endTime = r.str("end_time")
        subjectName = r.str("subject_name") ?? r.json("subject")?["name"]?.stringValue
        teacherName = r.str("teacher_name") ?? r.json("teacher")?["name"]?.stringValue
        classLabel = r.str("class_label")
        room = r.str("room")
    }

    public static let dayNames = ["", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"]
}

public struct ExamSchedule: Decodable, Identifiable, Hashable {
    public let id: Int
    public let subjectName: String?
    public let examDate: String?
    public let startTime: String?
    public let endTime: String?
    public let maxMarks: Int?
    public let room: String?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        id = r.int("id") ?? 0
        subjectName = r.str("subject_name") ?? r.json("subject")?["name"]?.stringValue
        examDate = r.str("exam_date", "date")
        startTime = r.str("start_time")
        endTime = r.str("end_time")
        maxMarks = r.int("max_marks")
        room = r.str("room")
    }
}
