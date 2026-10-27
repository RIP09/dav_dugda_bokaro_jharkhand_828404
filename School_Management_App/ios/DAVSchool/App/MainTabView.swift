import SwiftUI

/// Bottom tabs: Home · Menu (every module) · Search · Alerts · Me.
struct MainTabView: View {
    @EnvironmentObject private var session: SessionStore
    @State private var tab = 0

    var body: some View {
        TabView(selection: $tab) {
            NavigationStack { DashboardView().withRoutes() }
                .tabItem { Label("Home", systemImage: "house.fill") }.tag(0)
            NavigationStack { MenuView().withRoutes() }
                .tabItem { Label("Menu", systemImage: "square.grid.2x2.fill") }.tag(1)
            NavigationStack { SearchView().withRoutes() }
                .tabItem { Label("Search", systemImage: "magnifyingglass") }.tag(2)
            NavigationStack { NotificationsView().withRoutes() }
                .tabItem { Label("Alerts", systemImage: "bell.fill") }
                .badge(session.unreadNotifications)
                .tag(3)
            NavigationStack { ProfileView().withRoutes() }
                .tabItem { Label("Me", systemImage: "person.crop.circle.fill") }.tag(4)
        }
    }
}

/// The full menu from `meta/menu`, grouped, as a grid of tiles.
struct MenuView: View {
    @EnvironmentObject private var session: SessionStore
    @State private var filter = ""
    private let columns = [GridItem(.adaptive(minimum: 104), spacing: 12)]

    var body: some View {
        ScrollView {
            LazyVStack(alignment: .leading, spacing: 22) {
                ForEach(filteredGroups) { group in
                    VStack(alignment: .leading, spacing: 10) {
                        Text(group.group).font(.headline).padding(.horizontal, 4)
                        LazyVGrid(columns: columns, spacing: 12) {
                            ForEach(group.items) { item in
                                NavigationLink(value: Route.screen(item.screen, title: item.title)) {
                                    MenuTile(item: item)
                                }
                                .buttonStyle(.plain)
                            }
                        }
                    }
                }
                if !session.registryAvailable {
                    Text("This server runs an older version, so some modules are hidden. Ask the office to update the server.")
                        .font(.footnote).foregroundStyle(.secondary).padding(.top, 8)
                }
            }
            .padding()
        }
        .background(Theme.background)
        .navigationTitle("Menu")
        .searchable(text: $filter, prompt: "Find a module")
        .refreshable { await session.loadRegistry() }
        .toolbar { ToolbarItem(placement: .navigationBarLeading) { ChildSwitcher() } }
    }

    private var filteredGroups: [MenuGroup] {
        let q = filter.trimmingCharacters(in: .whitespaces).lowercased()
        guard !q.isEmpty else { return session.menu }
        return session.menu.compactMap { g in
            let items = g.items.filter { $0.title.lowercased().contains(q) || g.group.lowercased().contains(q) }
            return items.isEmpty ? nil : MenuGroup(group: g.group, items: items)
        }
    }
}

struct MenuTile: View {
    let item: MenuItem
    @State private var appeared = false

    var body: some View {
        VStack(spacing: 8) {
            Image(systemName: item.icon != nil ? Icons.symbol(item.icon) : Icons.screenSymbol(item.screen))
                .font(.title2)
                .foregroundStyle(.white)
                .frame(width: 48, height: 48)
                .background(Theme.brandGradient, in: RoundedRectangle(cornerRadius: 14, style: .continuous))
            Text(item.title)
                .font(.caption.weight(.medium))
                .multilineTextAlignment(.center)
                .lineLimit(2)
                .foregroundStyle(.primary)
        }
        .frame(maxWidth: .infinity, minHeight: 96)
        .padding(.vertical, 8)
        .background(Theme.card, in: RoundedRectangle(cornerRadius: 16, style: .continuous))
        .scaleEffect(appeared ? 1 : 0.92)
        .opacity(appeared ? 1 : 0)
        .onAppear { withAnimation(.spring(response: 0.4, dampingFraction: 0.8)) { appeared = true } }
    }
}
