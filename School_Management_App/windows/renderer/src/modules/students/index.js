// Students module (Windows app). Generic list/form screens come from modules/generic via the server's
// module registry; this file adds the students-specific screens, buttons and tabs.
import { openPdf, printPdf } from '../../ui/files';
export default {
  name: 'students',
  title: 'Students',
  keys: ["students", "student_documents", "promotions"],
  screens: { promotion: () => import('./PromotionWizard.vue') },
  extensions: {
  students: {
    pdfDoc: 'id-card',
    photoUpload: (id) => `students/${id}/photo`,
  },
  promotions: { note: 'Promotion history is permanent. Use the Promotion wizard to move students to the next class.' },
},
};
