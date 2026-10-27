import SwiftUI
import PhotosUI

struct ProfileView: View {
    @EnvironmentObject private var session: SessionStore
    @State private var showPassword = false
    @State private var showServer = false
    @State private var photoItem: PhotosPickerItem?
    @State private var uploading = false
    @State private var banner: Banner?
    @State private var confirmLogout = false

    var body: some View {
        List {
            if let u = session.user {
                Section {
                    HStack(spacing: 14) {
                        Avatar(name: u.name, url: u.photoUrl ?? u.student?.photoUrl, size: 64)
                        VStack(alignment: .leading, spacing: 3) {
                            Text(u.name).font(.title3.weight(.bold))
                            Text(u.designation ?? u.roleTitle).font(.subheadline).foregroundStyle(.secondary)
                            if let e = u.email { Text(e).font(.caption).foregroundStyle(.secondary) }
                        }
                    }
                    .padding(.vertical, 4)
                    if let sid = u.student?.id ?? (session.isStudent ? session.currentStudentId : nil) {
                        PhotosPicker(selection: $photoItem, matching: .images) {
                            HStack { Label("Change photo", systemImage: "camera.fill"); Spacer(); if uploading { ProgressView() } }
                        }
                        .onChange(of: photoItem) { item in
                            guard let item = item else { return }
                            Task { await uploadPhoto(item, studentId: sid) }
                        }
                    }
                }
                Section("Details") {
                    if let p = u.phone { InfoRow(label: "Phone", value: p) }
                    if let e = u.employeeId { InfoRow(label: "Employee ID", value: e) }
                    if let s = u.student {
                        InfoRow(label: "Class", value: s.classLabel ?? "—")
                        InfoRow(label: "Admission no.", value: s.admissionNo ?? "—")
                        InfoRow(label: "Roll no.", value: s.rollNo.map(String.init) ?? "—")
                    }
                    if let a = u.address { InfoRow(label: "Address", value: a) }
                }
                if session.isParent, !u.children.isEmpty {
                    Section("My children") {
                        ForEach(u.children) { c in
                            Button { session.selectedChild = c } label: {
                                HStack {
                                    Avatar(name: c.name, url: c.photoUrl, size: 36)
                                    VStack(alignment: .leading) {
                                        Text(c.name).foregroundStyle(.primary)
                                        Text([c.classLabel, c.admissionNo].compactMap { $0 }.joined(separator: " · ")).font(.caption).foregroundStyle(.secondary)
                                    }
                                    Spacer()
                                    if c.id == session.selectedChild?.id { Image(systemName: "checkmark.circle.fill").foregroundStyle(Theme.brand) }
                                }
                            }
                        }
                    }
                }
                if let sid = session.currentStudentId {
                    Section("Documents") {
                        DocumentButton(doc: .pdf("id-card", id: sid, title: "ID card"), symbol: "person.text.rectangle")
                        DocumentButton(doc: .pdf("fee-statement", id: sid, title: "Fee statement"), symbol: "indianrupeesign.circle")
                    }
                } else if Roles.staffRoles.contains(u.role) || u.role == "admin" {
                    Section("Documents") {
                        DocumentButton(doc: .pdf("staff-id-card", id: u.id, title: "My staff ID card"), symbol: "person.text.rectangle")
                    }
                }
            }
            Section("App") {
                Button { showPassword = true } label: { Label("Change password", systemImage: "key.fill") }
                Button { showServer = true } label: { Label("Server settings", systemImage: "server.rack") }
                if session.isAdmin {
                    NavigationLink(value: Route.screen("settings", title: "School settings")) { Label("School settings", systemImage: "gearshape.fill") }
                }
                LabeledContent("App", value: session.role.appName)
                LabeledContent("Version", value: AppConfig.version)
                LabeledContent("School", value: session.school?.shortName ?? session.school?.name ?? "DAV Dugda")
                LabeledContent("Principal", value: session.principalName)
            }
            Section {
                Button(role: .destructive) { confirmLogout = true } label: { Label("Sign out", systemImage: "rectangle.portrait.and.arrow.right") }
            }
        }
        .navigationTitle("Me")
        .refreshable { await session.refreshUser() }
        .sheet(isPresented: $showPassword) { ChangePasswordView { banner = Banner(text: $0) } }
        .sheet(isPresented: $showServer) { ServerSettingsView() }
        .confirmationDialog("Sign out of \(session.role.appName)?", isPresented: $confirmLogout, titleVisibility: .visible) {
            Button("Sign out", role: .destructive) { Task { await session.signOut() } }
        }
        .banner($banner)
    }

    private func uploadPhoto(_ item: PhotosPickerItem, studentId: Int) async {
        uploading = true; defer { uploading = false }
        guard let data = try? await item.loadTransferable(type: Data.self),
              let jpeg = UIImage(data: data)?.resizedForUpload(maxSide: 800).jpegData(compressionQuality: 0.8) else { return }
        do {
            try await session.api.uploadStudentPhoto(studentId: studentId, jpeg: jpeg)
            await session.refreshUser()
            banner = Banner(text: "Photo updated")
        } catch { banner = Banner(text: error.localizedDescription, isError: true) }
    }
}

struct ChangePasswordView: View {
    let done: (String) -> Void
    @EnvironmentObject private var session: SessionStore
    @Environment(\.dismiss) private var dismiss
    @State private var current = ""
    @State private var new = ""
    @State private var confirm = ""
    @State private var error: String?
    @State private var saving = false

    var body: some View {
        NavigationStack {
            Form {
                SecureField("Current password", text: $current).textContentType(.password)
                SecureField("New password (min 6)", text: $new).textContentType(.newPassword)
                SecureField("Type new password again", text: $confirm).textContentType(.newPassword)
                if let e = error { Text(e).foregroundStyle(Theme.danger) }
            }
            .navigationTitle("Change password")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) { Button("Cancel") { dismiss() } }
                ToolbarItem(placement: .confirmationAction) {
                    if saving { ProgressView() } else { Button("Save") { Task { await save() } } }
                }
            }
        }
    }

    private func save() async {
        guard new.count >= 6 else { error = "New password must be at least 6 characters."; return }
        guard new == confirm else { error = "The two new passwords don't match."; return }
        saving = true; defer { saving = false }
        do {
            let msg = try await session.api.changePassword(current: current, new: new)
            done(msg ?? "Password changed"); dismiss()
        } catch { self.error = error.localizedDescription }
    }
}
