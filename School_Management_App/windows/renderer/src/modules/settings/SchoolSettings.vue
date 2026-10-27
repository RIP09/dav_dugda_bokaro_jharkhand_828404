<template>
  <div class="page">
    <PageHead title="School settings" sub="Name, logo, principal and session shown on every receipt, certificate and report card." icon="settings">
      <router-link v-if="app.modules.settings" class="btn" to="/m/settings"><Icon name="tune" />All settings</router-link>
      <router-link v-if="app.modules.academic_years" class="btn" to="/m/academic_years"><Icon name="event_note" />Sessions</router-link>
    </PageHead>
    <div v-if="!isAdmin" class="infobar" style="margin-bottom:14px"><Icon name="info" /><div>Only the principal/admin can change these settings.</div></div>
    <div class="cols">
      <div class="card pad">
        <div class="form-grid">
          <div v-for="f in FIELDS" :key="f.k" class="field" :class="{ span2: f.wide }"><label>{{ f.l }}</label>
            <textarea v-if="f.wide" v-model="form[f.k]" class="input" rows="2" :disabled="!isAdmin" />
            <input v-else v-model="form[f.k]" class="input" :type="f.t || 'text'" :disabled="!isAdmin" /></div>
        </div>
        <div class="row" style="margin-top:18px"><span class="grow" /><button class="btn primary" :disabled="!isAdmin || saving" @click="save"><Icon name="save" />Save settings</button></div>
      </div>
      <div class="card pad preview">
        <h3>Logo</h3>
        <div class="logo-box"><img v-if="logoUrl" :src="logoUrl" alt="" /><span v-else>DAV</span></div>
        <label class="btn" :class="{ disabled: !isAdmin }"><Icon name="upload" />Upload logo<input type="file" accept="image/*" hidden :disabled="!isAdmin" @change="uploadLogo" /></label>
        <p class="small muted">PNG or JPG, square works best. Shown on the app, receipts, ID cards and certificates.</p>
        <div class="sig"><div class="small muted">Signed by</div><b>{{ form.principal_name || form.principal }}</b><div class="small muted">Principal</div></div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { reactive, ref, computed, onMounted } from 'vue';
import PageHead from '../../components/PageHead.vue';
import Icon from '../../components/Icon.vue';
import { get, put, upload, session } from '../../api/client';
import { app, normalizeSchool } from '../../state';
import { toast, toastError } from '../../ui/ui';
const FIELDS = [
  { k: 'school_name', l: 'School name', wide: false }, { k: 'school_short_name', l: 'Short name' },
  { k: 'principal_name', l: 'Principal name' }, { k: 'board', l: 'Board' },
  { k: 'affiliation_no', l: 'CBSE affiliation no.' }, { k: 'school_code', l: 'School code' },
  { k: 'phone', l: 'Phone' }, { k: 'email', l: 'Email', t: 'email' }, { k: 'website', l: 'Website' }, { k: 'receipt_prefix', l: 'Receipt prefix' },
  { k: 'late_fine_per_day', l: 'Late fee fine per day (₹)', t: 'number' }, { k: 'library_fine_per_day', l: 'Library fine per day (₹)', t: 'number' },
  { k: 'library_issue_days', l: 'Library issue days', t: 'number' }, { k: 'address', l: 'Address', wide: true },
];
const form = reactive({}); const saving = ref(false); const logoUrl = ref(null);
const isAdmin = computed(() => session.user?.role === 'admin');
onMounted(async () => { const d = (await get('school')).data || {}; Object.assign(form, d); if (!form.principal_name) form.principal_name = d.principal; logoUrl.value = d.logo_url || null; });
async function save() {
  saving.value = true;
  const body = { ...form, principal: form.principal_name, name: form.school_name, short_name: form.school_short_name };
  delete body.current_session; delete body.logo_url;
  for (const k of Object.keys(body)) if (body[k] === null || body[k] === '') delete body[k];
  try { const r = await put('school', body); app.school = normalizeSchool(r.data || form); toast('Settings saved'); } catch (e) { toastError(e); }
  saving.value = false;
}
async function uploadLogo(e) {
  const f = e.target.files?.[0]; if (!f) return;
  try { const r = await upload('uploads', f, 'file', { folder: 'logo' }); form.logo = r.data.path; logoUrl.value = r.data.url; await put('school', { logo: r.data.path, logo_url: r.data.url }).catch(() => {}); app.school = { ...app.school, logo_url: r.data.url }; toast('Logo uploaded'); }
  catch (err) { toastError(err); }
}
</script>
<style scoped>
.cols { display: grid; grid-template-columns: 1fr 320px; gap: 16px; align-items: start; }
.preview { text-align: center; }
.logo-box { width: 130px; height: 130px; margin: 14px auto; border-radius: 28px; display: grid; place-items: center; background: linear-gradient(135deg, #f59e0b, #ef4444); color: #fff; font-weight: 800; font-size: 28px; overflow: hidden; box-shadow: var(--shadow); }
.logo-box img { width: 100%; height: 100%; object-fit: cover; background: #fff; }
.sig { margin-top: 20px; padding: 14px; border-radius: 12px; background: var(--panel-2); border: 1px dashed var(--border); }
label.btn.disabled { opacity: .5; pointer-events: none; }
</style>
