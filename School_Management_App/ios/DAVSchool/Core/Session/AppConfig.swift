import Foundation

/// Which of the four apps this build is. Each Xcode target sets `DAVAppRole` in its Info.plist
/// (school | staff | parent | student) – exactly like the Android product flavors.
public enum AppRole: String, CaseIterable {
    case school, staff, parent, student

    public var appName: String {
        switch self {
        case .school: return "DAV School Admin"
        case .staff: return "DAV Staff"
        case .parent: return "DAV Parent"
        case .student: return "DAV Student"
        }
    }

    public var tagline: String {
        switch self {
        case .school: return "Principal & office – run the whole school"
        case .staff: return "Teachers, accountant, librarian & staff"
        case .parent: return "Your child's school, in your pocket"
        case .student: return "Timetable, homework, results & more"
        }
    }

    /// Demo account filled by the "Use demo account" button.
    public var demoLogin: (String, String) {
        switch self {
        case .school: return ("principal@davdugda.in", "principal123")
        case .staff: return ("teacher@davdugda.in", "teacher123")
        case .parent: return ("parent@davdugda.in", "parent123")
        case .student: return ("student@davdugda.in", "student123")
        }
    }

    /// Roles the server lets into this app.
    public var allowedRoles: Set<String> {
        switch self {
        case .school: return ["admin"]
        case .staff: return Roles.staffRoles
        case .parent: return ["parent"]
        case .student: return ["student"]
        }
    }
}

public enum AppConfig {
    public static let defaultServer = "https://dav-dugda-sms.sites.tab.bot/api/v1/"

    public static var role: AppRole {
        let raw = Bundle.main.object(forInfoDictionaryKey: "DAVAppRole") as? String
        return AppRole(rawValue: raw ?? "") ?? .school
    }

    public static var version: String {
        let v = Bundle.main.object(forInfoDictionaryKey: "CFBundleShortVersionString") as? String ?? "2.0"
        let b = Bundle.main.object(forInfoDictionaryKey: "CFBundleVersion") as? String ?? "1"
        return "\(v) (\(b))"
    }

    private static let serverKey = "dav.server.url"

    public static var serverURL: String {
        get { UserDefaults.standard.string(forKey: serverKey) ?? defaultServer }
        set { UserDefaults.standard.set(newValue, forKey: serverKey) }
    }
}
