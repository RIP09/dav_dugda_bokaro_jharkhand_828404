// Reports module (Windows app). Generic list/form screens come from modules/generic via the server's
// module registry; this file adds the reports-specific screens, buttons and tabs.

export default {
  name: 'reports',
  title: 'Reports',
  keys: [],
  screens: { search: () => import('./SearchView.vue'), reports: () => import('./ReportsHub.vue') },
  extensions: {},
};
