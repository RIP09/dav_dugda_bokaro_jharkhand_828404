import Foundation

public struct UserLite: Decodable, Identifiable, Hashable {
    public let id: Int
    public let name: String
    public let role: String?
    public let phone: String?
    public let email: String?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        id = r.int("id") ?? 0
        name = r.str("name") ?? "—"
        role = r.str("role")
        phone = r.str("phone")
        email = r.str("email")
    }
}

/// A student as returned by Phase-1 endpoints (also used for a parent's children).
public struct Student: Decodable, Identifiable, Hashable {
    public let id: Int
    public let userId: Int?
    public let admissionNo: String?
    public let rollNo: Int?
    public let classId: Int?
    public let sectionId: Int?
    public let name: String
    public let classLabel: String?
    public let fatherName: String?
    public let motherName: String?
    public let guardianPhone: String?
    public let bloodGroup: String?
    public let status: String?
    public let photoUrl: String?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        id = r.int("id") ?? 0
        userId = r.int("user_id")
        admissionNo = r.str("admission_no")
        rollNo = r.int("roll_no")
        classId = r.int("class_id")
        sectionId = r.int("section_id")
        let user = r.decode(UserLite.self, "user")
        name = r.str("name", "student_name") ?? user?.name ?? "Student"
        classLabel = r.str("class_label", "class_name")
        fatherName = r.str("father_name")
        motherName = r.str("mother_name")
        guardianPhone = r.str("guardian_phone")
        bloodGroup = r.str("blood_group")
        status = r.str("status")
        photoUrl = r.str("photo_url") ?? r.decode(JSONValue.self, "user")?["photo_url"]?.stringValue
    }
}

public struct User: Decodable, Identifiable, Hashable {
    public let id: Int
    public let name: String
    public let email: String?
    public let phone: String?
    public let role: String
    public let gender: String?
    public let dob: String?
    public let address: String?
    public let photoUrl: String?
    public let designation: String?
    public let employeeId: String?
    public let lastLoginAt: String?
    public let student: Student?
    public let children: [Student]

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        id = r.int("id") ?? 0
        name = r.str("name") ?? "—"
        email = r.str("email")
        phone = r.str("phone")
        role = r.str("role") ?? "student"
        gender = r.str("gender")
        dob = r.str("dob")
        address = r.str("address")
        photoUrl = r.str("photo_url")
        let staff = r.json("staff")
        designation = r.str("designation") ?? staff?["designation"]?.stringValue
        employeeId = r.str("employee_id") ?? staff?["employee_id"]?.stringValue
        lastLoginAt = r.str("last_login_at")
        student = r.decode(Student.self, "student")
        children = r.list(Student.self, "children")
    }

    public var roleTitle: String { Roles.title(role) }
    public var initials: String {
        name.split(separator: " ").prefix(2).compactMap { $0.first.map(String.init) }.joined().uppercased()
    }
}

public enum Roles {
    public static func title(_ role: String) -> String {
        switch role {
        case "admin": return "Principal / Admin"
        case "teacher": return "Teacher"
        case "accountant": return "Accountant"
        case "librarian": return "Librarian"
        case "staff": return "Staff"
        case "parent": return "Parent"
        case "student": return "Student"
        default: return role.capitalized
        }
    }
    public static let staffRoles: Set<String> = ["teacher", "accountant", "librarian", "staff"]
}

public struct LoginResponse: Decodable {
    public let token: String
    public let user: User
}

/// `GET school` – public school profile. Accepts both the v2 keys (name, principal_name …)
/// and the Phase-1 keys (school_name, principal …).
public struct SchoolProfile: Decodable, Hashable {
    public let name: String
    public let shortName: String?
    public let address: String?
    public let phone: String?
    public let email: String?
    public let affiliation: String?
    public let schoolCode: String?
    public let principalName: String?
    public let logoUrl: String?
    public let currentSession: String?
    public let board: String?

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        name = r.str("name", "school_name") ?? "DAV Public School, Dugda"
        shortName = r.str("short_name", "school_short_name")
        address = r.str("address")
        phone = r.str("phone")
        email = r.str("email")
        affiliation = r.str("affiliation_no", "affiliation")
        schoolCode = r.str("school_code")
        principalName = r.str("principal_name", "principal")
        logoUrl = r.str("logo_url")
        currentSession = r.str("current_session")
        board = r.str("board")
    }
}
