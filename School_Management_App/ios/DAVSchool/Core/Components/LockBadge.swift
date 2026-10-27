import SwiftUI

/// Shown on locked / permanent records: a lock icon and the reason. Editing is disabled wherever this appears.
struct LockBadge: View {
    var reason: String?
    var compact = false

    var body: some View {
        if compact {
            Image(systemName: "lock.fill")
                .font(.caption)
                .foregroundStyle(Theme.locked)
                .accessibilityLabel("Locked")
        } else {
            HStack(alignment: .top, spacing: 10) {
                Image(systemName: "lock.fill").font(.title3)
                VStack(alignment: .leading, spacing: 2) {
                    Text("Locked – permanent record").font(.subheadline.weight(.semibold))
                    Text(reason ?? "This record can't be edited or deleted.")
                        .font(.footnote)
                }
                Spacer(minLength: 0)
            }
            .foregroundStyle(Theme.locked)
            .padding(12)
            .background(Theme.accent.opacity(0.15), in: RoundedRectangle(cornerRadius: 12, style: .continuous))
            .overlay(RoundedRectangle(cornerRadius: 12).stroke(Theme.accent.opacity(0.4)))
        }
    }
}
