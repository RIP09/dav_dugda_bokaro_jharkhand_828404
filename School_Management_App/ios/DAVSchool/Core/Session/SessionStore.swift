import SwiftUI

/// App-wide state: server, signed-in user, module registry, menu, selected child (parents).
@MainActor
final class SessionStore: ObservableObject {
    let role: AppRole = AppConfig.role
    let api: APIClient

    @Published private(set) var user: User?
    @Published private(set) var school: SchoolProfile?
    @Published private(set) var modules: [String: ModuleMeta] = [:]
    @Published private(set) var menu: [MenuGroup] = []
    @Published var selectedChild: Student?
    @Published var unreadNotifications: Int = 0
    @Published var restoring = true
    /// Set when the registry endpoints are missing (old Phase-1 server) so the app falls back to built-in menus.
    @Published private(set) var registryAvailable = true

    private let tokenAccount = "token"

    init() {
        api = APIClient(baseURL: APIClient.normalizedBase(AppConfig.serverURL),
                        token: KeychainStore.load(account: "token"),
                        appRole: AppConfig.role.rawValue)
        api.onUnauthorized = { [weak self] in
            Task { @MainActor in self?.forceSignOut() }
        }
    }

    var isSignedIn: Bool { user != nil }
    var isAdmin: Bool { user?.role == "admin" }
    var isParent: Bool { user?.role == "parent" }
    var isStudent: Bool { user?.role == "student" }
    var isStaff: Bool { Roles.staffRoles.contains(user?.role ?? "") }
    var principalName: String { school?.principalName ?? "Prasanjeet Kumar Paul" }

    /// The student whose data a parent/student screen shows.
    var currentStudentId: Int? {
        if isParent { return selectedChild?.id ?? user?.children.first?.id }
        if isStudent { return user?.student?.id }
        return nil
    }

    func module(_ key: String) -> ModuleMeta? { modules[key] }
    func can(_ key: String, _ perm: KeyPath<ModulePermissions, Bool>) -> Bool {
        modules[key]?.permissions[keyPath: perm] ?? false
    }

    // MARK: Lifecycle

    func restore() async {
        defer { restoring = false }
        school = try? await api.school()
        guard api.token != nil else { return }
        do {
            let me = try await api.me()
            user = me
            if selectedChild == nil { selectedChild = me.children.first }
            await loadRegistry()
        } catch {
            if (error as? APIError)?.isUnauthorized == true { forceSignOut() }
            // Offline: keep the token, show login with the error next time.
        }
    }

    func signIn(login: String, password: String) async throws {
        let res = try await api.login(login, password: password)
        api.token = res.token
        KeychainStore.save(res.token, account: tokenAccount)
        user = res.user
        selectedChild = res.user.children.first
        if school == nil { school = try? await api.school() }
        await loadRegistry()
    }

    func refreshUser() async {
        if let me = try? await api.me() { user = me }
    }

    func loadRegistry() async {
        do {
            let list = try await api.modules()
            modules = Dictionary(list.map { ($0.key, $0) }, uniquingKeysWith: { a, _ in a })
            registryAvailable = true
        } catch {
            registryAvailable = false
        }
        if let m = try? await api.menu(), !m.isEmpty {
            menu = m
        } else {
            menu = FallbackMenu.groups(for: user?.role ?? "student", modules: Array(modules.values))
        }
        if let n = try? await api.notifications() { unreadNotifications = n.meta?.unread ?? n.data.filter { !$0.isRead }.count }
    }

    func signOut() async {
        await api.logout()
        forceSignOut()
    }

    func forceSignOut() {
        api.token = nil
        KeychainStore.save(nil, account: tokenAccount)
        user = nil
        modules = [:]
        menu = []
        selectedChild = nil
    }

    func setServer(_ raw: String) -> Bool {
        guard let url = APIClient.normalizedBase(raw) else { return false }
        AppConfig.serverURL = url.absoluteString
        api.baseURL = url
        school = nil
        Task { school = try? await api.school() }
        return true
    }
}
