import Foundation

/// Hostel: which server modules and screens belong to this area (folder Modules/Hostel/).
public extension ModuleArea {
    static let hostel = ModuleArea(
        id: "hostel",
        title: "Hostel",
        symbol: "bed.double.fill",
        moduleKeys: ["hostels", "hostel_rooms", "hostel_allocations"],
        screens: []
    )
}
