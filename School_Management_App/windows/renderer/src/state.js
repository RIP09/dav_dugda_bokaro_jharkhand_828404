// App-wide state: school profile, menu, module registry, notifications count.
import { reactive } from 'vue';
import { get } from './api/client';

export const PRINCIPAL_DEFAULT = 'Prasanjeet Kumar Paul';
export const app = reactive({ school: null, menu: [], modules: {}, moduleList: [], unread: 0, loaded: false, metaError: '' });

export function normalizeSchool(d = {}) {
  return {
    name: d.name || d.school_name || 'DAV Public School, Dugda',
    short_name: d.short_name || d.school_short_name || 'DAV Dugda',
    address: d.address || 'Dugda, Bokaro, Jharkhand 828404',
    phone: d.phone, email: d.email,
    affiliation_no: d.affiliation_no || d.affiliation, school_code: d.school_code,
    principal_name: d.principal_name || PRINCIPAL_DEFAULT,
    logo_url: d.logo_url || null,
    current_session: d.current_session, board: d.board || 'CBSE',
    raw: d,
  };
}

export async function loadSchool() {
  try { app.school = normalizeSchool((await get('school')).data); } catch (_) { app.school = app.school || normalizeSchool(); }
  return app.school;
}

export async function loadMeta() {
  app.metaError = '';
  const [mods, menu] = await Promise.allSettled([get('meta/modules'), get('meta/menu')]);
  if (mods.status === 'fulfilled') {
    app.moduleList = mods.value.data || [];
    app.modules = Object.fromEntries(app.moduleList.map(m => [m.key, m]));
  } else { app.moduleList = []; app.modules = {}; app.metaError = mods.reason?.message || 'Could not load modules'; }
  if (menu.status === 'fulfilled') app.menu = menu.value.data || [];
  else app.menu = fallbackMenu();
  app.loaded = true;
}

// Used only when the server does not offer meta/menu yet (older server): build a menu from meta/modules.
function fallbackMenu() {
  const groups = {};
  const order = [];
  const push = (g, item) => { if (!groups[g]) { groups[g] = []; order.push(g); } groups[g].push(item); };
  push('Home', { key: 'dashboard', title: 'Dashboard', icon: 'dashboard', screen: 'dashboard' });
  for (const m of app.moduleList) push(m.group || 'Other', { key: m.key, title: m.title, icon: m.icon, screen: m.key });
  push('Account', { key: 'profile', title: 'My profile', icon: 'person', screen: 'profile' });
  return order.map(g => ({ group: g, items: groups[g] }));
}

export async function refreshUnread() {
  try { const r = await get('notifications', { per_page: 1 }); app.unread = r.meta?.unread ?? 0; } catch (_) {}
}
