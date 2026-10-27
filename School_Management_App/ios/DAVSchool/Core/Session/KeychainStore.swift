import Foundation
#if canImport(Security)
import Security
#endif

/// Stores the login token in the iOS Keychain (never in plain UserDefaults).
enum KeychainStore {
    private static let service = "com.davdugda.sms.token"

    static func save(_ value: String?, account: String) {
        #if canImport(Security)
        let base: [String: Any] = [kSecClass as String: kSecClassGenericPassword,
                                   kSecAttrService as String: service,
                                   kSecAttrAccount as String: account]
        SecItemDelete(base as CFDictionary)
        guard let value = value, let data = value.data(using: .utf8) else { return }
        var add = base
        add[kSecValueData as String] = data
        add[kSecAttrAccessible as String] = kSecAttrAccessibleAfterFirstUnlock
        SecItemAdd(add as CFDictionary, nil)
        #else
        UserDefaults.standard.set(value, forKey: service + "." + account)
        #endif
    }

    static func load(account: String) -> String? {
        #if canImport(Security)
        let q: [String: Any] = [kSecClass as String: kSecClassGenericPassword,
                                kSecAttrService as String: service,
                                kSecAttrAccount as String: account,
                                kSecReturnData as String: true,
                                kSecMatchLimit as String: kSecMatchLimitOne]
        var out: AnyObject?
        guard SecItemCopyMatching(q as CFDictionary, &out) == errSecSuccess, let data = out as? Data else { return nil }
        return String(data: data, encoding: .utf8)
        #else
        return UserDefaults.standard.string(forKey: service + "." + account)
        #endif
    }
}
