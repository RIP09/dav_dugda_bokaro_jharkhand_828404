// Settings module (Windows app). Generic list/form screens come from modules/generic via the server's
// module registry; this file adds the settings-specific screens, buttons and tabs.

export default {
  name: 'settings',
  title: 'Settings',
  keys: ["settings"],
  screens: { settings: () => import('./SchoolSettings.vue'), import: () => import('./ImportWizard.vue'), backup: () => import('./BackupView.vue') },
  extensions: {},
};
