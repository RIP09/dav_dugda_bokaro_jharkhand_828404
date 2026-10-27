import Foundation

public enum AttendanceStatus: String, CaseIterable, Identifiable {
    case present, absent, late, halfDay = "half_day", leave
    public var id: String { rawValue }
    public var short: String {
        switch self { case .present: return "P"; case .absent: return "A"; case .late: return "L"; case .halfDay: return "H"; case .leave: return "LV" }
    }
    public var title: String {
        switch self { case .present: return "Present"; case .absent: return "Absent"; case .late: return "Late"; case .halfDay: return "Half day"; case .leave: return "Leave" }
    }
}

public struct AttendanceSheet: Decodable {
    public let date: String?
    public let sectionId: Int?
    public let alreadyMarked: Bool
    public var rows: [AttendanceSheetRow]

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        date = r.str("date")
        sectionId = r.int("section_id")
        alreadyMarked = r.bool("already_marked") ?? false
        rows = r.list(AttendanceSheetRow.self, "rows")
    }
}

public struct AttendanceSheetRow: Decodable, Identifiable, Hashable {
    public var id: Int { studentId }
    public let studentId: Int
    public let name: String
    public let rollNo: Int?
    public let admissionNo: String?
    public var status: String
    public var remark: String?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        studentId = r.int("student_id", "id") ?? 0
        name = r.str("name") ?? "Student"
        rollNo = r.int("roll_no")
        admissionNo = r.str("admission_no")
        status = r.str("status") ?? "present"
        remark = r.str("remark")
    }
}

public struct StaffSheet: Decodable {
    public let date: String?
    public let alreadyMarked: Bool
    public var rows: [StaffSheetRow]

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        date = r.str("date")
        alreadyMarked = r.bool("already_marked") ?? false
        rows = r.list(StaffSheetRow.self, "rows")
    }
}

public struct StaffSheetRow: Decodable, Identifiable, Hashable {
    public var id: Int { userId }
    public let userId: Int
    public let name: String
    public let designation: String?
    public var status: String
    public var inTime: String?
    public var outTime: String?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        userId = r.int("user_id", "id") ?? 0
        name = r.str("name") ?? "Staff"
        designation = r.str("designation")
        status = r.str("status") ?? "present"
        inTime = r.str("in_time")
        outTime = r.str("out_time")
    }
}

/// One student's month (`attendance/my`, `students/{id}/attendance`).
public struct AttendanceReport: Decodable {
    public let month: String?
    public let present: Int
    public let absent: Int
    public let late: Int
    public let halfDay: Int
    public let leave: Int
    public let total: Int
    public let percentage: Double
    public let days: [AttendanceDay]

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        month = r.str("month")
        present = r.int("present") ?? 0
        absent = r.int("absent") ?? 0
        late = r.int("late") ?? 0
        halfDay = r.int("half_day") ?? 0
        leave = r.int("leave") ?? 0
        total = r.int("total") ?? 0
        percentage = r.double("percentage") ?? 0
        days = r.list(AttendanceDay.self, "days")
    }
}

public struct AttendanceDay: Decodable, Hashable, Identifiable {
    public var id: String { date }
    public let date: String
    public let status: String
    public let remark: String?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        date = r.str("date") ?? ""
        status = r.str("status") ?? ""
        remark = r.str("remark")
    }
}

/// `attendance/report?section_id=&month=` – a section's month, day by day. Shape is read loosely.
public struct SectionMonthReport: Decodable {
    public let month: String?
    public let days: [String]
    public let students: [SectionMonthStudent]

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        month = r.str("month")
        let rawDays = r.decode([JSONValue].self, "days", "dates") ?? []
        days = rawDays.compactMap { $0.stringValue ?? $0["date"]?.stringValue }
        students = r.list(SectionMonthStudent.self, "students").isEmpty ? r.list(SectionMonthStudent.self, "rows") : r.list(SectionMonthStudent.self, "students")
    }
}

public struct SectionMonthStudent: Decodable, Identifiable, Hashable {
    public var id: Int { studentId }
    public let studentId: Int
    public let name: String
    public let rollNo: Int?
    public let statuses: [String: String]
    public let present: Int?
    public let absent: Int?
    public let percentage: Double?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        studentId = r.int("student_id", "id") ?? 0
        name = r.str("name") ?? "Student"
        rollNo = r.int("roll_no")
        var map: [String: String] = [:]
        if let obj = r.json("days")?.objectValue ?? r.json("statuses")?.objectValue {
            for (k, v) in obj { map[k] = v.stringValue ?? v["status"]?.stringValue }
        } else if let arr = r.json("days")?.arrayValue {
            for d in arr { if let date = d["date"]?.stringValue { map[date] = d["status"]?.stringValue } }
        }
        statuses = map.compactMapValues { $0 }
        let totals = r.json("totals")
        present = r.int("present") ?? totals?["present"]?.intValue
        absent = r.int("absent") ?? totals?["absent"]?.intValue
        percentage = r.double("percentage") ?? totals?["percentage"]?.doubleValue
    }
}

public struct SectionAttendance: Decodable, Identifiable, Hashable {
    public var id: Int { sectionId }
    public let sectionId: Int
    public let label: String
    public let present: Int
    public let absent: Int
    public let total: Int
    public let marked: Bool

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        sectionId = r.int("section_id", "id") ?? 0
        label = r.str("label", "class_label", "section") ?? "Section"
        present = r.int("present") ?? 0
        absent = r.int("absent") ?? 0
        total = r.int("total", "strength") ?? 0
        marked = r.bool("marked", "is_marked") ?? (present + absent > 0)
    }
}
