// Dashboard module (Windows app). Generic list/form screens come from modules/generic via the server's
// module registry; this file adds the dashboard-specific screens, buttons and tabs.

export default {
  name: 'dashboard',
  title: 'Dashboard',
  keys: [],
  screens: { dashboard: () => import('./DashboardView.vue') },
  extensions: {},
};
