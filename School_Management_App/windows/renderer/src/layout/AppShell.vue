<template>
  <div class="shell" :class="{ collapsed }">
    <Sidebar :collapsed="collapsed" @toggle="toggle" />
    <div class="main-col">
      <Topbar @search="ui.paletteOpen = true" />
      <transition name="slide"><div v-if="netState.offline" class="offline no-print"><Icon name="wifi_off" /> No connection to the school server. Your last action did not go through. <button class="btn sm" @click="retry">Retry</button></div></transition>
      <main class="main">
        <div v-if="!app.loaded" class="page"><div class="skeleton" style="height:120px;margin-bottom:16px" /><div class="skeleton" style="height:320px" /></div>
        <OfflineScreen v-else-if="bootError" :message="bootError" @retry="boot" />
        <router-view v-else v-slot="{ Component, route }">
          <transition name="route" mode="out-in"><component :is="Component" :key="route.fullPath.split('?')[0]" /></transition>
        </router-view>
      </main>
    </div>
    <CommandPalette />
  </div>
</template>
<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import Sidebar from './Sidebar.vue';
import Topbar from './Topbar.vue';
import CommandPalette from './CommandPalette.vue';
import OfflineScreen from './OfflineScreen.vue';
import Icon from '../components/Icon.vue';
import { app, loadSchool, loadMeta, refreshUnread } from '../state';
import { netState, get, session, saveSession } from '../api/client';
import { ui } from '../ui/ui';

const collapsed = ref(localStorage.getItem('dav.sidebar') === '1');
const toggle = () => { collapsed.value = !collapsed.value; localStorage.setItem('dav.sidebar', collapsed.value ? '1' : '0'); };
const bootError = ref('');
let timer;

async function boot() {
  bootError.value = '';
  try {
    const me = await get('auth/me');
    if (me?.data) await saveSession(session.token, { ...(session.user || {}), ...me.data });
  } catch (e) {
    if (e.status === 0) { bootError.value = e.message; app.loaded = true; return; }
  }
  await Promise.all([loadSchool(), loadMeta()]);
  refreshUnread();
}
function retry() { netState.offline = false; location.reload(); }
function onKey(e) {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); ui.paletteOpen = true; }
}
onMounted(() => { boot(); timer = setInterval(refreshUnread, 60000); window.addEventListener('keydown', onKey); });
onBeforeUnmount(() => { clearInterval(timer); window.removeEventListener('keydown', onKey); });
</script>
<style scoped>
.shell { display: flex; height: 100vh; }
.main-col { flex: 1; display: flex; flex-direction: column; min-width: 0; }
.main { flex: 1; overflow: auto; position: relative; }
.offline { display: flex; align-items: center; gap: 10px; padding: 9px 22px; background: #fff7ed; color: #9a3412; border-bottom: 1px solid #fed7aa; font-size: 13px; }
.slide-enter-active, .slide-leave-active { transition: all .25s var(--ease); } .slide-enter-from, .slide-leave-to { opacity: 0; transform: translateY(-100%); }
</style>
