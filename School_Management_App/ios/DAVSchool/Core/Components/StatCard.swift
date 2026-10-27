import SwiftUI

struct StatCard: View {
    let title: String
    let value: String
    var symbol: String = "square.grid.2x2"
    var color: Color = Theme.brand

    var body: some View {
        VStack(alignment: .leading, spacing: 10) {
            Image(systemName: symbol)
                .font(.title3)
                .foregroundStyle(.white)
                .frame(width: 38, height: 38)
                .background(color.gradient, in: RoundedRectangle(cornerRadius: 10, style: .continuous))
            Text(value).font(.title2.weight(.bold)).lineLimit(1).minimumScaleFactor(0.6)
            Text(title).font(.caption).foregroundStyle(.secondary).lineLimit(2)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .cardStyle()
    }
}

/// Avatar with photo or initials.
struct Avatar: View {
    let name: String
    var url: String?
    var size: CGFloat = 44

    var body: some View {
        Group {
            if let s = url, let u = URL(string: s) {
                AsyncImage(url: u) { img in img.resizable().scaledToFill() } placeholder: { initials }
            } else { initials }
        }
        .frame(width: size, height: size)
        .clipShape(Circle())
    }

    private var initials: some View {
        let t = name.split(separator: " ").prefix(2).compactMap { $0.first.map(String.init) }.joined().uppercased()
        return ZStack {
            Circle().fill(Theme.brand.opacity(0.15))
            Text(t.isEmpty ? "?" : t).font(.system(size: size * 0.38, weight: .semibold)).foregroundStyle(Theme.brand)
        }
    }
}

/// One label/value row in a detail screen.
struct InfoRow: View {
    let label: String
    let value: String
    var body: some View {
        HStack(alignment: .firstTextBaseline) {
            Text(label).foregroundStyle(.secondary)
            Spacer(minLength: 12)
            Text(value).multilineTextAlignment(.trailing).textSelection(.enabled)
        }
        .font(.subheadline)
    }
}
