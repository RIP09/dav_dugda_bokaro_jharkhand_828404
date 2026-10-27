// Teachers module (Windows app). Generic list/form screens come from modules/generic via the server's
// module registry; this file adds the teachers-specific screens, buttons and tabs.
import { openPdf, printPdf } from '../../ui/files';
export default {
  name: 'teachers',
  title: 'Teachers',
  keys: ["teachers"],
  screens: {},
  extensions: {
  teachers: { pdfDoc: 'staff-id-card' },
},
};
