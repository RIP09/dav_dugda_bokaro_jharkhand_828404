import Foundation

/// `GET dashboard/charts` (v2): cards + charts, role-aware.
public struct DashboardCharts: Decodable {
    public let cards: [DashCard]
    public let charts: [DashChart]

    public init(cards: [DashCard] = [], charts: [DashChart] = []) { self.cards = cards; self.charts = charts }

    public init(from decoder: Decoder) throws {
        // Accept either the bare object or a {"data": {...}} envelope.
        let r = try Row(decoder)
        if r.has("data"), let inner = r.decode(DashboardCharts.self, "data") {
            cards = inner.cards; charts = inner.charts; return
        }
        cards = r.list(DashCard.self, "cards")
        charts = r.list(DashChart.self, "charts")
    }
}

public struct DashCard: Decodable, Identifiable, Hashable {
    public var id: String { label }
    public let label: String
    public let value: String
    public let icon: String?
    public let color: String?

    public init(label: String, value: String, icon: String?, color: String? = nil) {
        self.label = label; self.value = value; self.icon = icon; self.color = color
    }

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        label = r.str("label", "title") ?? ""
        value = r.str("value") ?? "—"
        icon = r.str("icon")
        color = r.str("color")
    }
}

public struct DashChart: Decodable, Identifiable, Hashable {
    public let id: String
    public let title: String
    public let type: String
    public let labels: [String]
    public let datasets: [DashDataset]

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        title = r.str("title") ?? "Chart"
        id = r.str("id") ?? title
        type = r.str("type") ?? "bar"
        labels = (r.decode([JSONValue].self, "labels") ?? []).map { $0.stringValue ?? "" }
        datasets = r.list(DashDataset.self, "datasets")
    }
}

public struct DashDataset: Decodable, Hashable, Identifiable {
    public var id: String { label }
    public let label: String
    public let data: [Double]

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        label = r.str("label") ?? ""
        data = (r.decode([JSONValue].self, "data") ?? []).map { $0.doubleValue ?? 0 }
    }
}

/// Phase-1 `GET dashboard` (greeting, stats, notices, today's classes, children).
public struct Dashboard: Decodable {
    public let greeting: String?
    public let schoolName: String?
    public let date: String?
    public let stats: [DashCard]
    public let notices: [Notice]
    public let events: [SchoolEvent]
    public let todayClasses: [TimetableEntry]
    public let children: [Student]
    public let alerts: [String]
    public let unreadMessages: Int

    public init(from decoder: Decoder) throws {
        let r = try Row(decoder)
        greeting = r.str("greeting")
        schoolName = r.str("school_name")
        date = r.str("date")
        stats = r.list(DashCard.self, "stats")
        notices = r.list(Notice.self, "notices")
        events = r.list(SchoolEvent.self, "events")
        todayClasses = r.list(TimetableEntry.self, "today_classes")
        children = r.list(Student.self, "children")
        alerts = r.decode([String].self, "alerts") ?? []
        unreadMessages = r.int("unread_messages") ?? 0
    }
}
