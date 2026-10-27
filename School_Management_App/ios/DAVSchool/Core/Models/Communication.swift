import Foundation

public struct Homework: Decodable, Identifiable, Hashable {
    public let id: Int
    public let title: String
    public let description: String?
    public let assignedOn: String?
    public let dueDate: String?
    public let subjectName: String?
    public let teacherName: String?
    public let classLabel: String?
    public let sectionId: Int?
    public let submissionsCount: Int
    public let mySubmission: HomeworkSubmission?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        id = r.int("id") ?? 0
        title = r.str("title") ?? "Homework"
        description = r.str("description")
        assignedOn = r.str("assigned_on")
        dueDate = r.str("due_date")
        subjectName = r.str("subject_name")
        teacherName = r.str("teacher_name")
        classLabel = r.str("class_label")
        sectionId = r.int("section_id")
        submissionsCount = r.int("submissions_count") ?? 0
        mySubmission = r.decode(HomeworkSubmission.self, "my_submission")
    }
}

public struct HomeworkSubmission: Decodable, Identifiable, Hashable {
    public let id: Int
    public let studentName: String?
    public let content: String?
    public let submittedAt: String?
    public let status: String
    public let grade: String?
    public let feedback: String?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        id = r.int("id") ?? 0
        studentName = r.str("student_name")
        content = r.str("content")
        submittedAt = r.str("submitted_at", "created_at")
        status = r.str("status") ?? "submitted"
        grade = r.str("grade")
        feedback = r.str("feedback")
    }
}

public struct Notice: Decodable, Identifiable, Hashable {
    public let id: Int
    public let title: String
    public let body: String
    public let audience: String?
    public let publishDate: String?
    public let isPinned: Bool
    public let authorName: String?
    public var isRead: Bool
    public let readCount: Int?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        id = r.int("id") ?? 0
        title = r.str("title") ?? "Notice"
        body = r.str("body") ?? ""
        audience = r.str("audience")
        publishDate = r.str("publish_date", "created_at")
        isPinned = r.bool("is_pinned") ?? false
        authorName = r.str("author_name")
        isRead = r.bool("is_read", "read") ?? !(r.isNull("read_at"))
        readCount = r.int("reads_count", "read_count")
    }
}

public struct SchoolEvent: Decodable, Identifiable, Hashable {
    public let id: Int
    public let title: String
    public let description: String?
    public let startDate: String?
    public let endDate: String?
    public let type: String?
    public let venue: String?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        id = r.int("id") ?? 0
        title = r.str("title", "name") ?? "Event"
        description = r.str("description")
        startDate = r.str("start_date", "date", "event_date")
        endDate = r.str("end_date")
        type = r.str("type", "category")
        venue = r.str("venue", "location")
    }
}

public struct Message: Decodable, Identifiable, Hashable {
    public let id: Int
    public let senderId: Int?
    public let receiverId: Int?
    public let subject: String?
    public let body: String
    public let readAt: String?
    public let createdAt: String?
    public let senderName: String?
    public let receiverName: String?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        id = r.int("id") ?? 0
        senderId = r.int("sender_id")
        receiverId = r.int("receiver_id")
        subject = r.str("subject")
        body = r.str("body") ?? ""
        readAt = r.str("read_at")
        createdAt = r.str("created_at")
        senderName = r.str("sender_name") ?? r.json("sender")?["name"]?.stringValue
        receiverName = r.str("receiver_name") ?? r.json("receiver")?["name"]?.stringValue
    }
}

public struct AppNotification: Decodable, Identifiable, Hashable {
    public let id: String
    public let title: String
    public let body: String?
    public let module: String?
    public let recordId: Int?
    public var readAt: String?
    public let createdAt: String?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        id = r.str("id") ?? UUID().uuidString
        title = r.str("title") ?? "Notification"
        body = r.str("body", "message")
        module = r.str("module")
        recordId = r.int("record_id")
        readAt = r.str("read_at")
        createdAt = r.str("created_at")
    }

    public var isRead: Bool { readAt != nil }
}

public struct SearchHit: Decodable, Identifiable, Hashable {
    public var id: String { "\(module)-\(recordId)" }
    public let module: String
    public let recordId: Int
    public let title: String
    public let subtitle: String?
    public let screen: String?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        module = r.str("module") ?? ""
        recordId = r.int("id", "record_id") ?? 0
        title = r.str("title") ?? "—"
        subtitle = r.str("subtitle")
        screen = r.str("screen")
    }
}

/// One audit row: who changed what, when (old → new).
public struct AuditEntry: Decodable, Identifiable, Hashable {
    public let id: Int
    public let userName: String?
    public let action: String
    public let module: String?
    public let recordId: Int?
    public let recordTitle: String?
    public let changes: [FieldChange]
    public let ip: String?
    public let createdAt: String?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        id = r.int("id") ?? 0
        userName = r.str("user_name") ?? r.json("user")?["name"]?.stringValue
        action = r.str("action", "event") ?? "updated"
        module = r.str("module")
        recordId = r.int("record_id")
        recordTitle = r.str("record_title")
        ip = r.str("ip")
        createdAt = r.str("created_at")
        var list: [FieldChange] = []
        if let obj = r.json("changes")?.objectValue {
            for (k, v) in obj.sorted(by: { $0.key < $1.key }) {
                if v.objectValue != nil, v["old"] != nil || v["new"] != nil {
                    list.append(FieldChange(field: k, old: v["old"]?.displayString ?? "—", new: v["new"]?.displayString ?? "—"))
                } else {
                    list.append(FieldChange(field: k, old: "—", new: v.displayString))
                }
            }
        }
        changes = list
    }

    public var actionTitle: String {
        switch action {
        case "created", "create": return "Created"
        case "updated", "update": return "Edited"
        case "deleted", "delete": return "Deleted"
        default: return action.replacingOccurrences(of: "_", with: " ").capitalized
        }
    }
}

public struct FieldChange: Hashable, Identifiable {
    public var id: String { field }
    public let field: String
    public let old: String
    public let new: String
}

public struct LeaveRequest: Decodable, Identifiable, Hashable {
    public let id: Int
    public let type: String?
    public let fromDate: String?
    public let toDate: String?
    public let reason: String?
    public let status: String
    public let applicantName: String?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        id = r.int("id") ?? 0
        type = r.str("type")
        fromDate = r.str("from_date")
        toDate = r.str("to_date")
        reason = r.str("reason")
        status = r.str("status") ?? "pending"
        applicantName = r.str("applicant_name", "user_name", "student_name") ?? r.json("user")?["name"]?.stringValue
    }
}

public struct ImportResult: Decodable {
    public let created: Int
    public let updated: Int
    public let errors: [ImportRowError]

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        created = r.int("created") ?? 0
        updated = r.int("updated") ?? 0
        errors = r.list(ImportRowError.self, "errors")
    }
}

public struct ImportRowError: Decodable, Hashable, Identifiable {
    public var id: String { "\(row)-\(message)" }
    public let row: Int
    public let message: String

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        row = r.int("row") ?? 0
        message = r.str("message") ?? "Error"
    }
}

public struct UploadedFile: Decodable {
    public let path: String
    public let url: String?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        path = r.str("path") ?? ""
        url = r.str("url")
    }
}
