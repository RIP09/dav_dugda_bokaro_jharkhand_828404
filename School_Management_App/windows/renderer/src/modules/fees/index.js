// Fees module (Windows app). Generic list/form screens come from modules/generic via the server's
// module registry; this file adds the fees-specific screens, buttons and tabs.
import { openPdf, printPdf } from '../../ui/files';
export default {
  name: 'fees',
  title: 'Fees',
  keys: ["fee_types", "fee_structures", "concessions", "invoices", "payments"],
  screens: { fee_collect: () => import('./FeeCollect.vue'), fee_reminders: () => import('./FeeReminders.vue'), fee_generate: () => import('./FeeGenerate.vue') },
  extensions: {
  payments: { pdfDoc: 'receipt' },
  invoices: { pdfDoc: 'invoice' },
},
};
