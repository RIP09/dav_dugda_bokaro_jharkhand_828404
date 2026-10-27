// Exams module (Windows app). Generic list/form screens come from modules/generic via the server's
// module registry; this file adds the exams-specific screens, buttons and tabs.
import { openPdf, printPdf } from '../../ui/files';
export default {
  name: 'exams',
  title: 'Exams',
  keys: ["exams", "exam_schedules", "marks", "grades"],
  screens: { marks_entry: () => import('./MarksEntry.vue'), report_cards: () => import('./ReportCards.vue') },
  extensions: {
  exams: {
  },
},
};
