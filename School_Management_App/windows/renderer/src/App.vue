<template>
  <router-view v-slot="{ Component }">
    <transition name="route" mode="out-in"><component :is="Component" /></transition>
  </router-view>
  <ToastHost />
  <DialogHost />
</template>
<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import ToastHost from './components/ToastHost.vue';
import DialogHost from './components/DialogHost.vue';
import { onUnauthorized, saveSession, desktop } from './api/client';
import { toast } from './ui/ui';
import { printPage } from './ui/files';

const router = useRouter();
onUnauthorized(async () => {
  await saveSession(null, null);
  toast('Your session ended. Please sign in again.', 'info');
  router.replace('/login');
});
onMounted(() => {
  if (!desktop) return;
  desktop.onNavigate(p => router.push(p));
  desktop.onPrint(() => printPage());
  desktop.onReload(() => location.reload());
  desktop.onLogout(async () => { await saveSession(null, null); router.replace('/login'); });
});
</script>
