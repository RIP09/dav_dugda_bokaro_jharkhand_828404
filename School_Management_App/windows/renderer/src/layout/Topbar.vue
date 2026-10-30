<template>
  <header class="topbar no-print">
    <button class="search" @click="$emit('search')"><Icon name="search" /><span class="ph">Search students, staff, receipts, books…</span><kbd>Ctrl K</kbd></button>
    <span class="grow" />
    <span v-if="app.school?.current_session" class="badge indigo session"><Icon name="event_note" />Session {{ app.school.current_session }}</span>
    <div class="pop-wrap" v-click-away="() => (bell = false)">
      <button class="btn ghost icon bell" title="Notifications" @click="openBell"><Icon name="notifications" /><span v-if="app.unread" class="dot">{{ app.unread > 99 ? '99+' : app.unread }}</span></button>
      <transition name="pop">
        <div v-if="bell" class="pop notif">
          <div class="pop-head"><b>Notifications</b><span class="grow" /><button class="btn sm ghost" @click="readAll">Mark all read</button></div>
          <div v-if="loadingN" class="pad muted">Loading…</div>
          <div v-else-if="!notes.length" class="pad muted">No notifications yet.</div>
          <div v-for="n in notes" :key="n.id" class="nrow" :class="{ unread: !n.read_at }" @click="openNote(n)">
            <Icon name="circle" :size="9" class="ndot" /><div><div class="nt">{{ n.title }}</div><div class="nb">{{ n.body }}</div><div class="faint small">{{ ago(n.created_at) }}</div></div>
          </div>
          <router-link class="pop-foot" to="/s/notifications" @click="bell = false">See all notifications</router-link>
        </div>
      </transition>
    </div>
    <div class="pop-wrap" v-click-away="() => (menu = false)">
      <button class="profile" @click="menu = !menu">
        <img v-if="user?.photo_url" :src="user.photo_url" class="av" alt="" /><span v-else class="av">{{ initials }}</span>
        <span class="who"><b>{{ user?.name || 'User' }}</b><span>{{ roleLabel }}</span></span><Icon name="expand_more" :size="18" />
      </button>
      <transition name="pop">
        <div v-if="menu" class="pop pmenu" @click="menu = false">
          <router-link class="mi" to="/s/profile"><Icon name="person" />My profile & password</router-link>
          <router-link v-if="hasScreen('settings')" class="mi" to="/s/settings"><Icon name="settings" />School settings</router-link>
          <router-link class="mi" to="/setup"><Icon name="dns" />Server address</router-link>
          <a class="mi" @click="about"><Icon name="info" />About</a>
          <a v-if="isDesktop" class="mi" @click="desktop.checkUpdates()"><Icon name="system_update" />Check for updates</a>
          <div class="sep" />
          <a class="mi danger" @click="logout"><Icon name="logout" />Sign out</a>
        </div>
      </transition>
    </div>
  </header>
</template>
<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import Icon from '../components/Icon.vue';
import { app, refreshUnread } from '../state';
import { session, get, post, saveSession, isDesktop, desktop } from '../api/client';
import { routeFor } from '../router';
import { confirmBox } from '../ui/ui';
import { roleLabels, ago } from '../ui/format';

defineEmits(['search']);
const router = useRouter();
const bell = ref(false), menu = ref(false), notes = ref([]), loadingN = ref(false);
const user = computed(() => session.user);
const roleLabel = computed(() => roleLabels[user.value?.role] || user.value?.role || '');
const initials = computed(() => (user.value?.name || 'U').split(/\s+/).map(s => s[0]).slice(0, 2).join('').toUpperCase());
const hasScreen = (s) => (app.menu || []).some(g => g.items.some(i => i.screen === s));

const vClickAway = { mounted(el, b) { el._ca = (e) => { if (!el.contains(e.target)) b.value(); }; document.addEventListener('mousedown', el._ca); }, unmounted(el) { document.removeEventListener('mousedown', el._ca); } };

async function openBell() {
  bell.value = !bell.value; if (!bell.value) return;
  loadingN.value = true;
  try { const r = await get('notifications', { per_page: 8 }); notes.value = (r.data || []).slice(0, 8); app.unread = r.meta?.unread ?? app.unread; } catch (_) { notes.value = []; }
  loadingN.value = false;
}
async function openNote(n) {
  bell.value = false;
  if (!n.read_at) { post(`notifications/${n.id}/read`).then(refreshUnread).catch(() => {}); }
  if (n.module) router.push({ path: routeFor(n.module), query: n.record_id ? { id: n.record_id } : {} });
}
async function readAll() { await post('notifications/read-all').catch(() => {}); notes.value.forEach(n => (n.read_at = n.read_at || new Date().toISOString())); app.unread = 0; }
function about() { if (isDesktop) desktop.about(); else alert('DAV School Management 2.0.1\nPrincipal: Prasanjeet Kumar Paul'); }
async function logout() {
  if (!(await confirmBox({ title: 'Sign out?', message: 'You will need your password to sign in again.', ok: 'Sign out' }))) return;
  post('auth/logout').catch(() => {});
  await saveSession(null, null);
  router.replace('/login');
}
</script>
<style scoped>
.topbar { height: 62px; flex: none; display: flex; align-items: center; gap: 12px; padding: 0 22px; background: rgba(255,255,255,.85); backdrop-filter: blur(8px); border-bottom: 1px solid var(--border); position: relative; z-index: 50; }
.search { display: flex; align-items: center; gap: 10px; width: 440px; max-width: 46vw; height: 38px; padding: 0 12px; border-radius: 11px; border: 1px solid var(--border); background: var(--panel-2); color: var(--faint); font: inherit; cursor: text; transition: all .15s; justify-content: flex-start; }
.search:hover { border-color: #c7d2fe; background: #fff; }
.search .ph { flex: 1; min-width: 0; text-align: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
kbd { font: 11px ui-monospace, monospace; background: #fff; border: 1px solid var(--border); border-radius: 6px; padding: 2px 6px; color: var(--muted); }
.session { height: 30px; }
.pop-wrap { position: relative; }
.bell { position: relative; }
.dot { position: absolute; top: 3px; right: 1px; min-width: 18px; height: 18px; padding: 0 4px; border-radius: 9px; background: var(--danger); color: #fff; font-size: 10.5px; font-weight: 700; display: grid; place-items: center; border: 2px solid #fff; }
.profile { display: flex; align-items: center; gap: 10px; background: none; border: 0; cursor: pointer; padding: 4px 8px; border-radius: 12px; font: inherit; color: inherit; }
.profile:hover { background: rgba(15,23,42,.05); }
.av { width: 36px; height: 36px; border-radius: 50%; display: grid; place-items: center; background: linear-gradient(135deg, #818cf8, #c084fc); color: #fff; font-weight: 700; font-size: 13px; object-fit: cover; }
.who { display: flex; flex-direction: column; text-align: left; line-height: 1.2; } .who span { font-size: 12px; color: var(--muted); text-transform: capitalize; }
.pop { position: absolute; right: 0; top: calc(100% + 8px); background: #fff; border: 1px solid var(--border); border-radius: 14px; box-shadow: var(--shadow-lg); overflow: hidden; transform-origin: top right; }
.notif { width: 380px; } .pmenu { width: 240px; padding: 6px; }
.pop-head { display: flex; align-items: center; padding: 12px 14px; border-bottom: 1px solid var(--border); }
.pad { padding: 18px; }
.nrow { display: flex; gap: 10px; padding: 11px 14px; cursor: pointer; border-bottom: 1px solid #f1f5f9; }
.nrow:hover { background: #f8f9ff; } .ndot { color: transparent; margin-top: 5px; } .nrow.unread .ndot { color: var(--primary); }
.nt { font-weight: 600; font-size: 13.5px; } .nb { color: var(--muted); font-size: 12.5px; margin: 2px 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.pop-foot { display: block; text-align: center; padding: 10px; font-weight: 600; font-size: 13px; }
.mi { display: flex; align-items: center; gap: 10px; padding: 9px 10px; border-radius: 9px; color: var(--text); cursor: pointer; font-size: 13.5px; }
.mi:hover { background: var(--panel-2); } .mi.danger { color: var(--danger); } .sep { height: 1px; background: var(--border); margin: 5px 0; }
</style>
