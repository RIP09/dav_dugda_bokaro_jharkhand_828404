import SwiftUI

/// Parent–teacher (and staff) messages: inbox, sent, new message.
struct MessagesView: View {
    @EnvironmentObject private var session: SessionStore
    @State private var box = "inbox"
    @State private var messages: [Message] = []
    @State private var error: String?
    @State private var composing = false

    var body: some View {
        List {
            Picker("", selection: $box) { Text("Inbox").tag("inbox"); Text("Sent").tag("sent") }
                .pickerStyle(.segmented).listRowBackground(Color.clear)
            if let e = error { Text(e).foregroundStyle(Theme.danger) }
            if messages.isEmpty && error == nil {
                Text(box == "inbox" ? "No messages yet." : "You haven't sent any messages.").foregroundStyle(.secondary)
            }
            ForEach(messages) { m in
                NavigationLink(value: Route.message(m)) {
                    HStack(alignment: .top, spacing: 12) {
                        Avatar(name: (box == "inbox" ? m.senderName : m.receiverName) ?? "?", size: 38)
                        VStack(alignment: .leading, spacing: 2) {
                            HStack {
                                Text((box == "inbox" ? m.senderName : m.receiverName) ?? "—").font(.subheadline.weight(m.readAt == nil && box == "inbox" ? .bold : .regular))
                                Spacer()
                                Text(DateText.pretty(m.createdAt)).font(.caption2).foregroundStyle(.secondary)
                            }
                            if let s = m.subject { Text(s).font(.caption.weight(.semibold)) }
                            Text(m.body).font(.caption).foregroundStyle(.secondary).lineLimit(2)
                        }
                    }
                }
            }
        }
        .navigationTitle("Messages")
        .refreshable { await load() }
        .task(id: box) { await load() }
        .toolbar {
            ToolbarItem(placement: .primaryAction) { Button { composing = true } label: { Image(systemName: "square.and.pencil") } }
        }
        .sheet(isPresented: $composing) { ComposeMessageView(to: nil) { Task { box = "sent"; await load() } } }
    }

    private func load() async {
        do { messages = try await session.api.messages(box: box); error = nil } catch { self.error = error.localizedDescription }
    }
}

struct MessageDetailView: View {
    let message: Message
    @EnvironmentObject private var session: SessionStore
    @State private var full: Message?
    @State private var replying = false

    var body: some View {
        let m = full ?? message
        ScrollView {
            VStack(alignment: .leading, spacing: 10) {
                Text(m.subject ?? "Message").font(.title3.weight(.bold))
                Text("From \(m.senderName ?? "—") to \(m.receiverName ?? "—")").font(.caption).foregroundStyle(.secondary)
                Text(DateText.prettyDateTime(m.createdAt)).font(.caption2).foregroundStyle(.tertiary)
                Divider()
                Text(m.body).textSelection(.enabled)
            }
            .padding().frame(maxWidth: .infinity, alignment: .leading)
        }
        .navigationTitle("Message")
        .navigationBarTitleDisplayMode(.inline)
        .toolbar {
            if m.senderId != session.user?.id, m.senderId != nil {
                ToolbarItem(placement: .primaryAction) { Button("Reply") { replying = true } }
            }
        }
        .task { full = try? await session.api.openMessage(message.id) }
        .sheet(isPresented: $replying) {
            ComposeMessageView(to: m.senderId.map { ($0, m.senderName ?? "") }, subject: m.subject.map { $0.hasPrefix("Re:") ? $0 : "Re: \($0)" }) {}
        }
    }
}

struct ComposeMessageView: View {
    var to: (Int, String)?
    var subject: String? = nil
    let sent: () -> Void
    @EnvironmentObject private var session: SessionStore
    @Environment(\.dismiss) private var dismiss
    @State private var contacts: [UserLite] = []
    @State private var receiverId: Int = 0
    @State private var subj = ""
    @State private var body_ = ""
    @State private var error: String?
    @State private var sending = false

    var body: some View {
        NavigationStack {
            Form {
                if let to = to {
                    LabeledContent("To", value: to.1)
                } else {
                    Picker("To", selection: $receiverId) {
                        Text("Choose…").tag(0)
                        ForEach(contacts) { c in Text("\(c.name)\(c.role.map { " (\(Roles.title($0)))" } ?? "")").tag(c.id) }
                    }
                }
                TextField("Subject", text: $subj)
                TextField("Write your message", text: $body_, axis: .vertical).lineLimit(5...12)
                if let e = error { Text(e).foregroundStyle(Theme.danger) }
            }
            .navigationTitle("New message")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) { Button("Cancel") { dismiss() } }
                ToolbarItem(placement: .confirmationAction) {
                    if sending { ProgressView() } else {
                        Button("Send") { Task { await send() } }.disabled(body_.isEmpty || (to == nil && receiverId == 0))
                    }
                }
            }
            .task {
                subj = subject ?? ""
                if to == nil { contacts = (try? await session.api.contacts()) ?? [] }
            }
        }
    }

    private func send() async {
        sending = true; defer { sending = false }
        do {
            try await session.api.sendMessage(to: to?.0 ?? receiverId, subject: subj.isEmpty ? nil : subj, body: body_)
            sent(); dismiss()
        } catch { self.error = error.localizedDescription }
    }
}

/// In-app notification centre (fee reminders, new notices, results, leave updates …).
struct NotificationsView: View {
    @EnvironmentObject private var session: SessionStore
    @State private var items: [AppNotification] = []
    @State private var error: String?
    @State private var loading = true

    var body: some View {
        List {
            if loading && items.isEmpty { HStack { Spacer(); ProgressView(); Spacer() } }
            if let e = error, items.isEmpty { Text(e).foregroundStyle(.secondary) }
            if !loading && items.isEmpty && error == nil {
                EmptyStateView(title: "You're all caught up", symbol: "bell.slash")
            }
            ForEach(items) { n in
                Group {
                    if let module = n.module, let id = n.recordId, session.module(module) != nil {
                        NavigationLink(value: Route.record(module, id: id)) { row(n) }
                    } else {
                        row(n)
                    }
                }
                .simultaneousGesture(TapGesture().onEnded { Task { await markRead(n) } })
            }
        }
        .navigationTitle("Notifications")
        .refreshable { await load() }
        .task { await load() }
        .toolbar {
            if items.contains(where: { !$0.isRead }) {
                ToolbarItem(placement: .primaryAction) {
                    Button("Mark all read") {
                        Task {
                            try? await session.api.markAllNotificationsRead()
                            await load()
                        }
                    }
                }
            }
        }
    }

    private func row(_ n: AppNotification) -> some View {
        HStack(alignment: .top, spacing: 12) {
            Image(systemName: Icons.screenSymbol(n.module ?? "notifications"))
                .foregroundStyle(n.isRead ? Color.secondary : Theme.brand)
                .frame(width: 30)
            VStack(alignment: .leading, spacing: 3) {
                Text(n.title).font(.subheadline.weight(n.isRead ? .regular : .semibold))
                if let b = n.body { Text(b).font(.caption).foregroundStyle(.secondary).lineLimit(3) }
                Text(DateText.prettyDateTime(n.createdAt)).font(.caption2).foregroundStyle(.tertiary)
            }
        }
    }

    private func load() async {
        loading = true; defer { loading = false }
        do {
            let env = try await session.api.notifications()
            items = env.data
            session.unreadNotifications = env.meta?.unread ?? env.data.filter { !$0.isRead }.count
            error = nil
        } catch { self.error = error.localizedDescription }
    }

    private func markRead(_ n: AppNotification) async {
        guard !n.isRead else { return }
        try? await session.api.markNotificationRead(n.id)
        if let i = items.firstIndex(where: { $0.id == n.id }) { items[i].readAt = ISO8601DateFormatter().string(from: Date()) }
        session.unreadNotifications = max(0, session.unreadNotifications - 1)
    }
}
