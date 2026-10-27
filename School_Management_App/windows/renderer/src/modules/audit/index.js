// Audit module (Windows app): the change log of every record (who / when / old → new). Read-only, admin only.
export default {
  name: 'audit',
  title: 'Audit log',
  keys: ['audit'],
  screens: { audit: () => import('./AuditLog.vue') },
  extensions: {},
};
