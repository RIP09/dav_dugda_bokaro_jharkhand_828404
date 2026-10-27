<template>
  <AuthLayout>
    <h1>Sign in</h1>
    <p class="muted lead">Use your school login (email or mobile number).</p>
    <form @submit.prevent="login">
      <div class="field"><label>Email or mobile</label><input v-model.trim="form.login" class="input" autocomplete="username" autofocus placeholder="principal@davdugda.in" /></div>
      <div class="field" style="margin-top:14px">
        <label>Password</label>
        <div class="pw"><input v-model="form.password" :type="show ? 'text' : 'password'" class="input" autocomplete="current-password" /><button type="button" class="btn ghost icon sm" @click="show = !show"><Icon :name="show ? 'visibility_off' : 'visibility'" /></button></div>
      </div>
      <transition name="pop"><div v-if="error" class="err-box"><Icon name="error" />{{ error }}</div></transition>
      <button class="btn primary wide" :disabled="busy || !form.login || !form.password"><Icon v-if="busy" name="progress_activity" class="spin" />Sign in</button>
    </form>
    <div v-if="isDemo" class="demo">
      <div class="small muted">Demo server — tap a login to fill it:</div>
      <div class="row" style="gap:6px;margin-top:8px">
        <button v-for="d in demos" :key="d.login" class="chip" type="button" @click="fill(d)">{{ d.label }}</button>
      </div>
    </div>
    <div class="foot small muted">Server: {{ host }} · <router-link to="/setup">change</router-link></div>
  </AuthLayout>
</template>
<script setup>
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import AuthLayout from './AuthLayout.vue';
import Icon from '../components/Icon.vue';
import { session, post, saveSession } from '../api/client';
const router = useRouter();
const form = reactive({ login: '', password: '' });
const busy = ref(false), error = ref(''), show = ref(false);
const host = computed(() => { try { return new URL(session.server).host; } catch (_) { return session.server; } });
const isDemo = computed(() => /dav-dugda-sms|127\.0\.0\.1|localhost/.test(session.server));
const demos = [
  { label: 'Principal', login: 'principal@davdugda.in', password: 'principal123' },
  { label: 'Admin', login: 'admin@davdugda.in', password: 'admin123' },
  { label: 'Teacher', login: 'teacher@davdugda.in', password: 'teacher123' },
  { label: 'Accountant', login: 'accountant@davdugda.in', password: 'accountant123' },
  { label: 'Librarian', login: 'librarian@davdugda.in', password: 'librarian123' },
  { label: 'Parent', login: 'parent@davdugda.in', password: 'parent123' },
  { label: 'Student', login: 'student@davdugda.in', password: 'student123' },
];
function fill(d) { form.login = d.login; form.password = d.password; error.value = ''; }
async function login() {
  busy.value = true; error.value = '';
  try {
    const r = await post('auth/login', { login: form.login, password: form.password, app: 'web' });
    await saveSession(r.data.token, r.data.user);
    router.replace('/dashboard');
  } catch (e) {
    error.value = e.status === 422 || e.status === 401 ? (e.errors ? Object.values(e.errors)[0][0] : e.message) : e.message;
  } finally { busy.value = false; }
}
</script>
<style scoped>
.lead { margin: 8px 0 24px; }
.pw { position: relative; } .pw .btn { position: absolute; right: 4px; top: 4px; }
.wide { width: 100%; justify-content: center; height: 44px; margin-top: 22px; font-size: 15px; }
.err-box { display: flex; gap: 8px; align-items: center; margin-top: 14px; padding: 10px 12px; border-radius: 10px; background: var(--danger-50); color: #9f1239; font-size: 13px; }
.demo { margin-top: 26px; padding: 14px; border-radius: 12px; background: var(--panel-2); border: 1px dashed var(--border); }
.foot { margin-top: 22px; text-align: center; }
</style>
