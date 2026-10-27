<template>
  <AuthLayout>
    <h1>{{ session.firstRunDone ? 'Server address' : 'Welcome 👋' }}</h1>
    <p class="muted lead">Connect this PC to your school server. If you are just trying the software, use the free demo server.</p>
    <div class="field">
      <label>Server address</label>
      <input v-model="url" class="input" placeholder="e.g. https://davdugda.alwaysdata.net" @keydown.enter="test" />
      <span class="help">Ask the person who set up your school server. "/api/v1" is added automatically.</span>
    </div>
    <div class="row" style="margin-top:14px">
      <button class="btn" :disabled="busy" @click="test"><Icon name="network_check" />Test connection</button>
      <button class="btn ghost" @click="useDemo"><Icon name="science" />Use demo server</button>
    </div>
    <transition name="pop">
      <div v-if="result" class="result" :class="result.ok ? 'ok' : 'bad'">
        <Icon :name="result.ok ? 'check_circle' : 'error'" />
        <span v-if="result.ok">Connected to <b>{{ result.school }}</b></span><span v-else>{{ result.error }}</span>
      </div>
    </transition>
    <button class="btn primary wide" :disabled="busy || !url" @click="save"><Icon v-if="busy" name="progress_activity" class="spin" />Save & continue</button>
    <router-link v-if="session.token && session.server" to="/dashboard" class="back">← Back to the app</router-link>
  </AuthLayout>
</template>
<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import AuthLayout from './AuthLayout.vue';
import Icon from '../components/Icon.vue';
import { session, setServer, testServer, DEMO_SERVER, saveSession } from '../api/client';
const router = useRouter();
const url = ref(session.server || ''), busy = ref(false), result = ref(null);
async function test() { busy.value = true; result.value = await testServer(url.value); busy.value = false; return result.value; }
function useDemo() { url.value = DEMO_SERVER; test(); }
async function save() {
  const r = await test(); if (!r.ok) return;
  const changed = session.server !== r.url;
  await setServer(r.url);
  if (changed) await saveSession(null, null);
  router.replace(session.token ? '/dashboard' : '/login');
}
</script>
<style scoped>
.lead { margin: 8px 0 24px; line-height: 1.5; }
.result { display: flex; gap: 10px; align-items: center; margin-top: 14px; padding: 11px 14px; border-radius: 11px; font-size: 13.5px; }
.result.ok { background: var(--success-50); color: #065f46; } .result.bad { background: var(--danger-50); color: #9f1239; }
.wide { width: 100%; justify-content: center; height: 44px; margin-top: 22px; font-size: 15px; }
.back { display: block; text-align: center; margin-top: 16px; font-size: 13px; }
</style>
