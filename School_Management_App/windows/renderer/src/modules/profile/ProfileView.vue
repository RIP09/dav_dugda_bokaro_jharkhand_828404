<template>
  <div class="page">
    <PageHead title="My profile" icon="person" />
    <div class="cols">
      <div class="card pad prof fade-up">
        <div class="av">{{ ini }}</div>
        <h2>{{ user?.name }}</h2>
        <span class="badge indigo">{{ roleLabels[user?.role] || user?.role }}</span>
        <dl class="kv" style="margin-top:18px;text-align:left">
          <dt>Email</dt><dd>{{ user?.email || '—' }}</dd><dt>Mobile</dt><dd>{{ user?.phone || '—' }}</dd>
          <dt>School</dt><dd>{{ app.school?.name }}</dd><dt>Principal</dt><dd>{{ app.school?.principal_name }}</dd>
          <dt>Server</dt><dd class="small">{{ session.server }}</dd><dt>App version</dt><dd>{{ session.version }}</dd>
        </dl>
      </div>
      <div class="card pad fade-up">
        <h3>Change password</h3>
        <div class="field" style="margin-top:14px"><label>Current password</label><input v-model="pw.current_password" type="password" class="input" /></div>
        <div class="field" style="margin-top:12px"><label>New password</label><input v-model="pw.new_password" type="password" class="input" /><span class="help">At least 6 characters.</span></div>
        <div class="field" style="margin-top:12px"><label>Repeat new password</label><input v-model="pw.repeat" type="password" class="input" :class="{ invalid: pw.repeat && pw.repeat !== pw.new_password }" /></div>
        <p v-if="err" class="small" style="color:var(--danger)">{{ err }}</p>
        <button class="btn primary" style="margin-top:16px" :disabled="!pw.current_password || (pw.new_password || '').length < 6 || pw.new_password !== pw.repeat" @click="change"><Icon name="key" />Change password</button>
      </div>
    </div>
  </div>
</template>
<script setup>
import { reactive, ref, computed } from 'vue';
import PageHead from '../../components/PageHead.vue';
import Icon from '../../components/Icon.vue';
import { session, post } from '../../api/client';
import { app } from '../../state';
import { toast } from '../../ui/ui';
import { roleLabels } from '../../ui/format';
const user = computed(() => session.user);
const ini = computed(() => (user.value?.name || 'U').split(' ').map(s => s[0]).slice(0, 2).join(''));
const pw = reactive({ current_password: '', new_password: '', repeat: '' }); const err = ref('');
async function change() {
  err.value = '';
  try { const r = await post('auth/change-password', { current_password: pw.current_password, new_password: pw.new_password, new_password_confirmation: pw.repeat }); toast(r.message || 'Password changed'); Object.assign(pw, { current_password: '', new_password: '', repeat: '' }); }
  catch (e) { err.value = e.errors ? Object.values(e.errors)[0][0] : e.message; }
}
</script>
<style scoped>
.cols { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: start; }
.prof { text-align: center; }
.av { width: 84px; height: 84px; border-radius: 50%; margin: 6px auto 12px; display: grid; place-items: center; font-size: 28px; font-weight: 800; color: #fff; background: linear-gradient(135deg, #818cf8, #c084fc); box-shadow: 0 12px 30px -10px rgba(129,140,248,.8); }
</style>
