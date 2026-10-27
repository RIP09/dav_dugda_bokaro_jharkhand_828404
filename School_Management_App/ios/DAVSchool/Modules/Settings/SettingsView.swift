import SwiftUI

/// School settings (name, logo, session, principal name, fines …) come from the `settings` module, so every
/// setting the server defines is editable here, with history.
struct SettingsView: View {
    @EnvironmentObject private var session: SessionStore

    var body: some View {
        List {
            Section {
                VStack(alignment: .leading, spacing: 4) {
                    Text(session.school?.name ?? "DAV Public School, Dugda").font(.headline)
                    if let a = session.school?.address { Text(a).font(.caption).foregroundStyle(.secondary) }
                    Text("Principal: \(session.principalName)").font(.caption)
                    if let s = session.school?.currentSession { Text("Session \(s)").font(.caption) }
                }
            }
            Section("Manage") {
                if session.module("settings") != nil {
                    NavigationLink(value: Route.module("settings", title: "School settings")) { Label("School name, logo, session & rules", systemImage: "building.columns.fill") }
                }
                NavigationLink(value: Route.screen("import", title: "Import")) { Label("Import from Excel", systemImage: "square.and.arrow.down.on.square.fill") }
                NavigationLink(value: Route.screen("backup", title: "Backup")) { Label("Backup & export", systemImage: "externaldrive.fill") }
                NavigationLink(value: Route.screen("audit", title: "Edit history")) { Label("Edit history (audit log)", systemImage: "clock.arrow.circlepath") }
                NavigationLink(value: Route.screen("promotion", title: "Promotion")) { Label("New session / promotion", systemImage: "arrow.up.circle.fill") }
            }
            Section("All areas") {
                ForEach(ModuleArea.all.filter { area in area.moduleKeys.contains { session.module($0) != nil } }) { area in
                    NavigationLink {
                        AreaView(area: area)
                    } label: {
                        Label(area.title, systemImage: area.symbol)
                    }
                }
            }
        }
        .navigationTitle("Settings")
    }
}

/// Lists every module of one area (e.g. Hostel → Hostels, Rooms, Allocations).
struct AreaView: View {
    let area: ModuleArea
    @EnvironmentObject private var session: SessionStore

    var body: some View {
        List {
            ForEach(area.moduleKeys.compactMap { session.module($0) }) { m in
                NavigationLink(value: Route.module(m.key, title: m.title)) {
                    HStack {
                        Label(m.title, systemImage: Icons.symbol(m.icon))
                        Spacer()
                        if m.lockable { Image(systemName: "lock.fill").font(.caption).foregroundStyle(.tertiary) }
                    }
                }
            }
        }
        .navigationTitle(area.title)
    }
}
