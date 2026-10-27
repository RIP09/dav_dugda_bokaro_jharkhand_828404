// Timetable module (Windows app). Generic list/form screens come from modules/generic via the server's
// module registry; this file adds the timetable-specific screens, buttons and tabs.

export default {
  name: 'timetable',
  title: 'Timetable',
  keys: ["timetable"],
  screens: { timetable: () => import('./TimetableView.vue') },
  extensions: {},
};
