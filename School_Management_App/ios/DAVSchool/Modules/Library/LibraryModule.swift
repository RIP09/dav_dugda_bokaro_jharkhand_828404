import Foundation

/// Library: which server modules and screens belong to this area (folder Modules/Library/).
public extension ModuleArea {
    static let library = ModuleArea(
        id: "library",
        title: "Library",
        symbol: "books.vertical",
        moduleKeys: ["books", "book_issues"],
        screens: []
    )
}
