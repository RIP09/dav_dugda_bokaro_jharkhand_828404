import SwiftUI

/// Loading / error / empty states with friendly words.
struct LoadingView: View {
    var text = "Loading…"
    var body: some View {
        VStack(spacing: 12) {
            ProgressView()
            Text(text).font(.subheadline).foregroundStyle(.secondary)
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .padding(40)
    }
}

struct ErrorStateView: View {
    let message: String
    var retry: (() async -> Void)?
    var body: some View {
        VStack(spacing: 14) {
            Image(systemName: "wifi.exclamationmark").font(.system(size: 40)).foregroundStyle(.secondary)
            Text(message).multilineTextAlignment(.center).font(.subheadline).foregroundStyle(.secondary)
            if let retry = retry {
                Button { Task { await retry() } } label: { Label("Try again", systemImage: "arrow.clockwise") }
                    .buttonStyle(.borderedProminent).tint(Theme.brand)
            }
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .padding(32)
    }
}

/// iOS 16-compatible empty state (ContentUnavailableView is iOS 17+).
struct EmptyStateView: View {
    let title: String
    var message: String? = nil
    var symbol = "tray"
    var body: some View {
        VStack(spacing: 10) {
            Image(systemName: symbol).font(.system(size: 42)).foregroundStyle(Theme.brand.opacity(0.6))
            Text(title).font(.headline)
            if let m = message { Text(m).font(.subheadline).foregroundStyle(.secondary).multilineTextAlignment(.center) }
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .padding(32)
    }
}

enum LoadState<T> {
    case loading
    case loaded(T)
    case failed(String)
}

/// Loads something once (and on pull-to-refresh) and shows loading / error / content.
struct AsyncContent<T, Content: View>: View {
    let load: () async throws -> T
    @ViewBuilder let content: (T, @escaping () async -> Void) -> Content
    @State private var state: LoadState<T> = .loading

    init(load: @escaping () async throws -> T, @ViewBuilder content: @escaping (T, @escaping () async -> Void) -> Content) {
        self.load = load
        self.content = content
    }

    var body: some View {
        Group {
            switch state {
            case .loading: LoadingView()
            case .failed(let msg): ErrorStateView(message: msg, retry: reload)
            case .loaded(let value): content(value, reload)
            }
        }
        .task { if case .loading = state { await reload() } }
    }

    func reload() async {
        do { state = .loaded(try await load()) }
        catch is CancellationError { }
        catch { state = .failed(error.localizedDescription) }
    }
}

/// A small coloured capsule, e.g. "PAID", "PENDING".
struct StatusPill: View {
    let text: String
    var color: Color? = nil
    var body: some View {
        let c = color ?? Theme.statusColor(text)
        Text(text.replacingOccurrences(of: "_", with: " ").uppercased())
            .font(.caption2.weight(.bold))
            .padding(.horizontal, 8).padding(.vertical, 3)
            .background(c.opacity(0.15), in: Capsule())
            .foregroundStyle(c)
    }
}

/// Toast-style banner shown at the top after an action.
struct Banner: Equatable {
    var text: String
    var isError = false
}

struct BannerModifier: ViewModifier {
    @Binding var banner: Banner?
    func body(content: Content) -> some View {
        content.overlay(alignment: .top) {
            if let b = banner {
                HStack(spacing: 10) {
                    Image(systemName: b.isError ? "exclamationmark.triangle.fill" : "checkmark.circle.fill")
                    Text(b.text).font(.subheadline.weight(.medium)).lineLimit(3)
                }
                .foregroundStyle(.white)
                .padding(.horizontal, 16).padding(.vertical, 12)
                .background((b.isError ? Theme.danger : Theme.success).gradient, in: Capsule())
                .shadow(radius: 8, y: 4)
                .padding(.top, 8)
                .transition(.move(edge: .top).combined(with: .opacity))
                .task(id: b.text) {
                    try? await Task.sleep(nanoseconds: 3_000_000_000)
                    withAnimation { banner = nil }
                }
            }
        }
        .animation(.spring(response: 0.35), value: banner)
    }
}

extension View {
    func banner(_ banner: Binding<Banner?>) -> some View { modifier(BannerModifier(banner: banner)) }
}
