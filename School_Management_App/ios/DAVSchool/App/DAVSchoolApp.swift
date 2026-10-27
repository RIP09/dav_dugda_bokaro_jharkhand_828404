import SwiftUI

/// One codebase, four apps. The target's Info.plist key `DAVAppRole` decides which app this is.
@main
struct DAVSchoolApp: App {
    @StateObject private var session = SessionStore()

    var body: some Scene {
        WindowGroup {
            RootView()
                .environmentObject(session)
                .tint(Theme.brand)
        }
    }
}

struct RootView: View {
    @EnvironmentObject private var session: SessionStore

    var body: some View {
        Group {
            if session.restoring {
                SplashView()
            } else if session.isSignedIn {
                MainTabView()
                    .transition(.opacity.combined(with: .scale(scale: 0.98)))
            } else {
                LoginView()
                    .transition(.opacity)
            }
        }
        .animation(.easeInOut(duration: 0.3), value: session.isSignedIn)
        .animation(.easeInOut(duration: 0.3), value: session.restoring)
        .task { await session.restore() }
    }
}

struct SplashView: View {
    @State private var pulse = false
    var body: some View {
        ZStack {
            Theme.brandGradient.ignoresSafeArea()
            VStack(spacing: 16) {
                Image(systemName: "graduationcap.fill")
                    .font(.system(size: 64))
                    .foregroundStyle(.white)
                    .scaleEffect(pulse ? 1.08 : 0.94)
                    .animation(.easeInOut(duration: 0.9).repeatForever(autoreverses: true), value: pulse)
                Text(AppConfig.role.appName).font(.title2.weight(.bold)).foregroundStyle(.white)
                Text("DAV Public School, Dugda").font(.subheadline).foregroundStyle(.white.opacity(0.85))
            }
        }
        .onAppear { pulse = true }
    }
}
