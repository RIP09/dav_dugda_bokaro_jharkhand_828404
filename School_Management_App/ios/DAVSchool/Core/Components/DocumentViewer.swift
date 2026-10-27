import SwiftUI
import QuickLook

/// A downloaded file ready to preview.
struct PreviewFile: Identifiable {
    let id = UUID()
    let url: URL
    let title: String
}

/// QuickLook preview (PDF, Excel, images) – pinch to zoom, print and share from the toolbar.
struct QuickLookView: UIViewControllerRepresentable {
    let url: URL

    func makeUIViewController(context: Context) -> QLPreviewController {
        let c = QLPreviewController()
        c.dataSource = context.coordinator
        return c
    }

    func updateUIViewController(_ uiViewController: QLPreviewController, context: Context) {
        context.coordinator.url = url
        uiViewController.reloadData()
    }

    func makeCoordinator() -> Coordinator { Coordinator(url: url) }

    final class Coordinator: NSObject, QLPreviewControllerDataSource {
        var url: URL
        init(url: URL) { self.url = url }
        func numberOfPreviewItems(in controller: QLPreviewController) -> Int { 1 }
        func previewController(_ controller: QLPreviewController, previewItemAt index: Int) -> QLPreviewItem { url as NSURL }
    }
}

struct PreviewSheet: View {
    let file: PreviewFile
    @Environment(\.dismiss) private var dismiss

    var body: some View {
        NavigationStack {
            QuickLookView(url: file.url)
                .ignoresSafeArea(edges: .bottom)
                .navigationTitle(file.title)
                .navigationBarTitleDisplayMode(.inline)
                .toolbar {
                    ToolbarItem(placement: .cancellationAction) { Button("Done") { dismiss() } }
                    ToolbarItem(placement: .primaryAction) {
                        ShareLink(item: file.url) { Image(systemName: "square.and.arrow.up") }
                    }
                }
        }
    }
}

/// Downloads a server document (PDF/Excel/zip) with the login token and opens it.
@MainActor
final class DocumentLoader: ObservableObject {
    @Published var preview: PreviewFile?
    @Published var loadingId: String?
    @Published var error: String?

    func open(_ doc: ServerDocument, api: APIClient) {
        guard loadingId == nil else { return }
        loadingId = doc.id
        Task {
            defer { loadingId = nil }
            do {
                let file = try await api.download(doc.path, query: doc.query, fallbackName: doc.title.replacingOccurrences(of: " ", with: "_"))
                preview = PreviewFile(url: file.url, title: doc.title)
            } catch {
                self.error = error.localizedDescription
            }
        }
    }
}

/// A row/button that opens a document.
struct DocumentButton: View {
    let doc: ServerDocument
    var symbol = "doc.richtext.fill"
    @EnvironmentObject private var session: SessionStore
    @StateObject private var loader = DocumentLoader()

    var body: some View {
        Button { loader.open(doc, api: session.api) } label: {
            HStack {
                Label(doc.title, systemImage: symbol)
                Spacer()
                if loader.loadingId != nil { ProgressView() } else { Image(systemName: "chevron.right").font(.caption).foregroundStyle(.tertiary) }
            }
        }
        .sheet(item: $loader.preview) { PreviewSheet(file: $0) }
        .alert("Couldn't open", isPresented: Binding(get: { loader.error != nil }, set: { if !$0 { loader.error = nil } })) {
            Button("OK", role: .cancel) {}
        } message: { Text(loader.error ?? "") }
    }
}
