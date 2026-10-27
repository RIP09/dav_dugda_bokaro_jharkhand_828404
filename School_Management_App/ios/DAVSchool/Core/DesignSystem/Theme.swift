import SwiftUI

/// Colours, spacing and icons shared by all four apps. Each app has its own brand colour (like Android).
enum Theme {
    static var brand: Color {
        switch AppConfig.role {
        case .school: return Color(red: 0.10, green: 0.24, blue: 0.56)   // DAV navy
        case .staff: return Color(red: 0.00, green: 0.47, blue: 0.42)    // teal
        case .parent: return Color(red: 0.90, green: 0.32, blue: 0.00)   // saffron
        case .student: return Color(red: 0.42, green: 0.11, blue: 0.60)  // purple
        }
    }
    static let accent = Color(red: 0.96, green: 0.62, blue: 0.04)        // DAV gold
    static let success = Color(red: 0.13, green: 0.59, blue: 0.33)
    static let danger = Color(red: 0.80, green: 0.16, blue: 0.16)
    static let warning = Color(red: 0.93, green: 0.55, blue: 0.0)
    static let locked = Color(red: 0.45, green: 0.36, blue: 0.10)
    static let card = Color(uiColor: .secondarySystemGroupedBackground)
    static let background = Color(uiColor: .systemGroupedBackground)

    static var brandGradient: LinearGradient {
        LinearGradient(colors: [brand, brand.opacity(0.75)], startPoint: .topLeading, endPoint: .bottomTrailing)
    }

    static func named(_ name: String?) -> Color {
        switch (name ?? "").lowercased() {
        case "green", "success": return success
        case "red", "danger", "error": return danger
        case "orange", "warning", "amber": return warning
        case "purple", "violet": return .purple
        case "teal", "cyan": return .teal
        case "pink": return .pink
        case "indigo": return .indigo
        case "blue", "primary", "info": return .blue
        default:
            if let hex = name, hex.hasPrefix("#"), hex.count == 7, let v = Int(hex.dropFirst(), radix: 16) {
                return Color(red: Double((v >> 16) & 0xFF) / 255, green: Double((v >> 8) & 0xFF) / 255, blue: Double(v & 0xFF) / 255)
            }
            return brand
        }
    }

    static func attendanceColor(_ status: String) -> Color {
        switch status {
        case "present": return success
        case "absent": return danger
        case "late": return warning
        case "half_day": return .orange
        case "leave": return .blue
        case "holiday": return .purple
        default: return .gray
        }
    }

    static func statusColor(_ status: String?) -> Color {
        switch (status ?? "").lowercased() {
        case "paid", "approved", "issued", "active", "present", "admitted", "returned", "resolved", "published", "submitted", "reviewed": return success
        case "unpaid", "rejected", "cancelled", "absent", "overdue", "lost", "inactive": return danger
        case "partial", "pending", "draft", "new", "open", "issued_out", "late", "in_progress": return warning
        default: return .secondary
        }
    }
}

/// Maps the server's Material icon names (and our own area names) to SF Symbols.
enum Icons {
    static func symbol(_ name: String?) -> String {
        let n = (name ?? "").lowercased()
        let map: [String: String] = [
            "dashboard": "square.grid.2x2.fill", "home": "house.fill", "search": "magnifyingglass",
            "person_add": "person.badge.plus", "how_to_reg": "person.crop.circle.badge.checkmark",
            "students": "graduationcap.fill", "school": "graduationcap.fill", "person": "person.fill", "people": "person.2.fill",
            "groups": "person.3.fill", "staff": "person.3.fill", "badge": "person.text.rectangle", "parents": "figure.2.and.child.holdinghands",
            "family_restroom": "figure.2.and.child.holdinghands", "teachers": "person.crop.rectangle.stack.fill",
            "class": "rectangle.3.group.fill", "classes": "rectangle.3.group.fill", "menu_book": "book.fill", "book": "book.fill",
            "library_books": "books.vertical.fill", "subject": "text.book.closed.fill", "calendar_month": "calendar",
            "calendar_today": "calendar", "event": "calendar.badge.clock", "schedule": "clock.fill", "timetable": "calendar.day.timeline.left",
            "fact_check": "checklist", "attendance": "checklist", "assignment": "pencil.and.ruler.fill", "homework": "pencil.and.ruler.fill",
            "quiz": "doc.text.magnifyingglass", "exams": "doc.text.magnifyingglass", "grade": "star.fill", "edit_note": "square.and.pencil",
            "workspace_premium": "rosette", "card_membership": "rosette", "military_tech": "rosette", "upgrade": "arrow.up.circle.fill",
            "payments": "indianrupeesign.circle.fill", "fees": "indianrupeesign.circle.fill", "currency_rupee": "indianrupeesign.circle.fill",
            "receipt": "doc.plaintext.fill", "receipt_long": "doc.plaintext.fill", "due": "exclamationmark.circle.fill",
            "account_balance": "building.columns.fill", "account_balance_wallet": "wallet.pass.fill", "savings": "banknote.fill",
            "request_quote": "doc.badge.gearshape", "work": "briefcase.fill", "inventory": "shippingbox.fill", "inventory_2": "shippingbox.fill",
            "category": "square.stack.3d.up.fill", "hotel": "bed.double.fill", "bed": "bed.double.fill", "apartment": "building.2.fill",
            "directions_bus": "bus.fill", "local_shipping": "box.truck.fill", "transport": "bus.fill", "badge_outlined": "person.text.rectangle",
            "contact_phone": "phone.fill", "call": "phone.fill", "mail": "envelope.fill", "local_post_office": "envelope.fill",
            "campaign": "megaphone.fill", "notifications": "bell.fill", "chat": "bubble.left.and.bubble.right.fill",
            "forum": "bubble.left.and.bubble.right.fill", "feedback": "exclamationmark.bubble.fill", "report": "exclamationmark.bubble.fill",
            "settings": "gearshape.fill", "history": "clock.arrow.circlepath", "manage_history": "clock.arrow.circlepath",
            "upload_file": "square.and.arrow.up.fill", "upload": "square.and.arrow.up.fill", "backup": "externaldrive.fill",
            "download": "square.and.arrow.down.fill", "print": "printer.fill", "picture_as_pdf": "doc.richtext.fill",
            "bar_chart": "chart.bar.fill", "insights": "chart.line.uptrend.xyaxis", "event_busy": "calendar.badge.minus",
            "beach_access": "sun.max.fill", "lock": "lock.fill", "description": "doc.text.fill", "folder": "folder.fill",
            "visibility": "eye.fill", "logout": "rectangle.portrait.and.arrow.right", "info": "info.circle.fill",
            "money": "indianrupeesign.circle.fill", "check_circle": "checkmark.circle.fill", "cancel": "xmark.circle.fill",
        ]
        if let s = map[n] { return s }
        if n.contains("fee") || n.contains("pay") { return "indianrupeesign.circle.fill" }
        if n.contains("student") { return "graduationcap.fill" }
        if n.contains("book") { return "book.fill" }
        if n.contains("bus") { return "bus.fill" }
        return n.isEmpty ? "square.grid.2x2" : (n.contains(".") ? n : "circle.grid.2x2.fill")
    }

    /// Icon for a custom screen id from meta/menu.
    static func screenSymbol(_ screen: String) -> String {
        switch screen {
        case "dashboard": return "square.grid.2x2.fill"
        case "attendance_take": return "checklist"
        case "attendance_report": return "calendar"
        case "staff_attendance": return "person.badge.clock.fill"
        case "timetable": return "calendar.day.timeline.left"
        case "marks_entry": return "square.and.pencil"
        case "report_cards": return "star.fill"
        case "fee_collect": return "indianrupeesign.circle.fill"
        case "fee_reminders": return "bell.badge.fill"
        case "payroll_run": return "banknote.fill"
        case "promotion": return "arrow.up.circle.fill"
        case "import": return "square.and.arrow.down.on.square.fill"
        case "backup": return "externaldrive.fill"
        case "audit": return "clock.arrow.circlepath"
        case "settings": return "gearshape.fill"
        case "messages": return "bubble.left.and.bubble.right.fill"
        case "notifications": return "bell.fill"
        case "calendar": return "calendar.badge.clock"
        case "search": return "magnifyingglass"
        case "profile": return "person.crop.circle.fill"
        default: return ModuleArea.area(forModule: screen)?.symbol ?? "square.grid.2x2"
        }
    }
}

extension View {
    /// Rounded card look used across the apps.
    func cardStyle(padding: CGFloat = 14) -> some View {
        self.padding(padding)
            .background(Theme.card, in: RoundedRectangle(cornerRadius: 16, style: .continuous))
            .shadow(color: .black.opacity(0.05), radius: 6, x: 0, y: 2)
    }
}
