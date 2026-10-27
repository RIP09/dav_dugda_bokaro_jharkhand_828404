import Foundation

/// Payroll: which server modules and screens belong to this area (folder Modules/Payroll/).
public extension ModuleArea {
    static let payroll = ModuleArea(
        id: "payroll",
        title: "Payroll",
        symbol: "banknote.fill",
        moduleKeys: ["salary_structures", "payslips"],
        screens: ["payroll_run"],
        documents: { key, record in
        guard key == "payslips" else { return [] }
        return [.pdf("salary-slip", id: record.id, title: "Salary slip")]
        }
    )
}
