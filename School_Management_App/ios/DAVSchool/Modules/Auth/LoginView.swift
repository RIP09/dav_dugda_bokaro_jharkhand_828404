import SwiftUI

struct LoginView: View {
    @EnvironmentObject private var session: SessionStore
    @State private var login = ""
    @State private var password = ""
    @State private var busy = false
    @State private var error: String?
    @State private var showServer = false
    @State private var showPassword = false
    @State private var appeared = false
    @FocusState private var focus: Field?
    enum Field { case login, password }

    var body: some View {
        ZStack {
            Theme.brandGradient.ignoresSafeArea()
            Circle().fill(.white.opacity(0.08)).frame(width: 320).offset(x: 150, y: -320)
            Circle().fill(Theme.accent.opacity(0.18)).frame(width: 240).offset(x: -160, y: 330)

            ScrollView {
                VStack(spacing: 22) {
                    header
                        .padding(.top, 48)
                        .offset(y: appeared ? 0 : -20)
                        .opacity(appeared ? 1 : 0)
                    form
                        .offset(y: appeared ? 0 : 30)
                        .opacity(appeared ? 1 : 0)
                    Button { showServer = true } label: {
                        Label("Server settings", systemImage: "server.rack").font(.footnote.weight(.medium))
                    }
                    .foregroundStyle(.white.opacity(0.9))
                    Text("Version \(AppConfig.version)").font(.caption2).foregroundStyle(.white.opacity(0.6))
                }
                .padding(.horizontal, 24)
                .padding(.bottom, 30)
            }
        }
        .onAppear { withAnimation(.spring(response: 0.6, dampingFraction: 0.8)) { appeared = true } }
        .sheet(isPresented: $showServer) { ServerSettingsView() }
    }

    private var header: some View {
        VStack(spacing: 10) {
            ZStack {
                Circle().fill(.white).frame(width: 92, height: 92).shadow(radius: 10, y: 4)
                if let logo = session.school?.logoUrl, let u = URL(string: logo) {
                    AsyncImage(url: u) { $0.resizable().scaledToFit() } placeholder: { schoolMark }
                        .frame(width: 70, height: 70).clipShape(Circle())
                } else { schoolMark }
            }
            Text(session.school?.name ?? "DAV Public School, Dugda")
                .font(.title2.weight(.bold)).foregroundStyle(.white).multilineTextAlignment(.center)
            Text(session.role.appName + " · " + session.role.tagline)
                .font(.subheadline).foregroundStyle(.white.opacity(0.85)).multilineTextAlignment(.center)
        }
    }

    private var schoolMark: some View {
        Image(systemName: "graduationcap.fill").font(.system(size: 40)).foregroundStyle(Theme.brand)
    }

    private var form: some View {
        VStack(spacing: 14) {
            Text("Sign in").font(.title3.weight(.semibold)).frame(maxWidth: .infinity, alignment: .leading)

            HStack {
                Image(systemName: "person.fill").foregroundStyle(.secondary)
                TextField("Email or phone", text: $login)
                    .textContentType(.username)
                    .keyboardType(.emailAddress)
                    .textInputAutocapitalization(.never)
                    .autocorrectionDisabled()
                    .focused($focus, equals: .login)
                    .submitLabel(.next)
                    .onSubmit { focus = .password }
            }
            .padding(14)
            .background(Color(uiColor: .tertiarySystemFill), in: RoundedRectangle(cornerRadius: 12))

            HStack {
                Image(systemName: "lock.fill").foregroundStyle(.secondary)
                Group {
                    if showPassword { TextField("Password", text: $password) } else { SecureField("Password", text: $password) }
                }
                .textContentType(.password)
                .textInputAutocapitalization(.never)
                .focused($focus, equals: .password)
                .submitLabel(.go)
                .onSubmit { Task { await signIn() } }
                Button { showPassword.toggle() } label: { Image(systemName: showPassword ? "eye.slash" : "eye") }
                    .foregroundStyle(.secondary)
            }
            .padding(14)
            .background(Color(uiColor: .tertiarySystemFill), in: RoundedRectangle(cornerRadius: 12))

            if let e = error {
                Label(e, systemImage: "exclamationmark.triangle.fill")
                    .font(.footnote).foregroundStyle(Theme.danger)
                    .frame(maxWidth: .infinity, alignment: .leading)
                    .transition(.opacity)
            }

            Button { Task { await signIn() } } label: {
                HStack {
                    if busy { ProgressView().tint(.white) }
                    Text(busy ? "Signing in…" : "Sign in").fontWeight(.semibold)
                }
                .frame(maxWidth: .infinity).padding(.vertical, 14)
            }
            .background(Theme.brandGradient, in: RoundedRectangle(cornerRadius: 12))
            .foregroundStyle(.white)
            .disabled(busy || login.isEmpty || password.isEmpty)
            .opacity(login.isEmpty || password.isEmpty ? 0.7 : 1)

            Button {
                let d = session.role.demoLogin
                login = d.0; password = d.1
            } label: {
                Label("Use demo account", systemImage: "wand.and.stars").font(.subheadline.weight(.medium))
            }
            .padding(.top, 2)
        }
        .padding(20)
        .background(.regularMaterial, in: RoundedRectangle(cornerRadius: 22, style: .continuous))
        .shadow(color: .black.opacity(0.15), radius: 20, y: 10)
    }

    private func signIn() async {
        guard !login.isEmpty, !password.isEmpty else { return }
        busy = true; error = nil
        defer { busy = false }
        do {
            try await session.signIn(login: login.trimmingCharacters(in: .whitespaces), password: password)
        } catch {
            withAnimation { self.error = error.localizedDescription }
        }
    }
}

struct ServerSettingsView: View {
    @EnvironmentObject private var session: SessionStore
    @Environment(\.dismiss) private var dismiss
    @State private var url = AppConfig.serverURL
    @State private var status: String?
    @State private var ok = false
    @State private var testing = false

    var body: some View {
        NavigationStack {
            Form {
                Section {
                    TextField("https://your-school-server/api/v1/", text: $url)
                        .keyboardType(.URL).textInputAutocapitalization(.never).autocorrectionDisabled()
                } header: { Text("School server address") } footer: {
                    Text("Ask your school office for this address. The demo server is filled in by default.")
                }
                Section {
                    Button { Task { await test() } } label: {
                        HStack { Text("Test connection"); Spacer(); if testing { ProgressView() } }
                    }
                    if let s = status {
                        Label(s, systemImage: ok ? "checkmark.circle.fill" : "xmark.octagon.fill")
                            .foregroundStyle(ok ? Theme.success : Theme.danger)
                    }
                    Button("Use demo server") { url = AppConfig.defaultServer }
                }
            }
            .navigationTitle("Server settings")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) { Button("Cancel") { dismiss() } }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Save") {
                        if session.setServer(url) { dismiss() } else { status = "That address doesn't look right."; ok = false }
                    }
                }
            }
        }
    }

    private func test() async {
        testing = true; defer { testing = false }
        guard let base = APIClient.normalizedBase(url) else { status = "That address doesn't look right."; ok = false; return }
        let probe = APIClient(baseURL: base, appRole: session.role.rawValue)
        do {
            let s = try await probe.school()
            status = "Connected: \(s.name)"; ok = true
        } catch {
            status = error.localizedDescription; ok = false
        }
    }
}
