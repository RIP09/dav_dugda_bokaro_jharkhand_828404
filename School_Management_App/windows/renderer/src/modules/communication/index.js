// Communication module (Windows app). Generic list/form screens come from modules/generic via the server's
// module registry; this file adds the communication-specific screens, buttons and tabs.
import { post } from '../../api/client';
export default {
  name: 'communication',
  title: 'Communication',
  keys: ["notices", "events", "complaints"],
  screens: { messages: () => import('./MessagesView.vue'), notifications: () => import('./NotificationsView.vue'), calendar: () => import('./CalendarView.vue') },
  extensions: {
  notices: {
    onOpen: (row) => post(`notices/${row.id}/read`).catch(() => {}),
    tabs: [{ key: 'reads', label: 'Who read it', icon: 'mark_email_read', component: () => import('./NoticeReads.vue'), roles: ['admin', 'teacher'] }],
  },
},
};
