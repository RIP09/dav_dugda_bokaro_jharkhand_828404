// Staff module (Windows app). Generic list/form screens come from modules/generic via the server's
// module registry; this file adds the staff-specific screens, buttons and tabs.
import { openPdf, printPdf } from '../../ui/files';
export default {
  name: 'staff',
  title: 'Staff',
  keys: ["staff", "staff_attendance"],
  screens: { staff_attendance: () => import('./StaffAttendance.vue') },
  extensions: {
  staff: { pdfDoc: 'staff-id-card' },
},
};
