// Profile module (Windows app). Generic list/form screens come from modules/generic via the server's
// module registry; this file adds the profile-specific screens, buttons and tabs.

export default {
  name: 'profile',
  title: 'Profile',
  keys: [],
  screens: { profile: () => import('./ProfileView.vue') },
  extensions: {},
};
