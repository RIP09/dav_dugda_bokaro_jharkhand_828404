import { createRouter, createWebHashHistory } from 'vue-router';
import { session } from './api/client';
import { screens } from './modules/registry';

const Shell = () => import('./layout/AppShell.vue');

export const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/setup', component: () => import('./views/ServerSetup.vue'), meta: { public: true } },
    { path: '/login', component: () => import('./views/LoginView.vue'), meta: { public: true } },
    {
      path: '/', component: Shell, children: [
        { path: '', redirect: '/dashboard' },
        { path: 'dashboard', component: screens.dashboard, meta: { screen: 'dashboard' } },
        { path: 'm/:module', component: () => import('./modules/generic/ModuleView.vue'), meta: { generic: true } },
        { path: 's/:screen', component: () => import('./views/ScreenHost.vue') },
      ],
    },
    { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
  ],
});

router.beforeEach((to) => {
  if (!session.server && to.path !== '/setup') return '/setup';
  if (!to.meta.public && !session.token) return '/login';
  return true;
});

/** Route for a meta/menu item (`screen` is a custom screen id or a module key). */
export function routeFor(screen, preferModule = false) {
  if (!screen) return '/dashboard';
  if (String(screen).startsWith('module:')) return '/m/' + screen.slice(7);
  if (screen === 'dashboard') return '/dashboard';
  if (screens[screen] && !preferModule) return '/s/' + screen;
  return '/m/' + screen;
}

/** Menu items can name a custom screen and a module with the same id (e.g. staff_attendance, timetable):
 *  the first occurrence opens the custom screen, a later duplicate (or a title saying "records") opens the module list. */
export function menuRoutes(items) {
  const seen = new Set();
  return items.map(it => {
    const dup = seen.has(it.screen); seen.add(it.screen);
    const preferModule = dup || (/record/i.test(it.title || '') && screens[it.screen]);
    return { ...it, to: routeFor(it.screen, !!preferModule) };
  });
}
