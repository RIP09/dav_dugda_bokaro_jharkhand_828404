// Payroll module (Windows app). Generic list/form screens come from modules/generic via the server's
// module registry; this file adds the payroll-specific screens, buttons and tabs.

export default {
  name: 'payroll',
  title: 'Payroll',
  keys: ["salary_structures", "payslips"],
  screens: { payroll_run: () => import('./PayrollRun.vue') },
  extensions: {
  payslips: { pdfDoc: 'salary-slip' },
},
};
