<template>
  <div class="page">
    <PageHead title="Staff attendance" sub="Mark teachers and staff for the day, with in/out time." icon="badge" color="#a855f7">
      <input v-model="date" type="date" class="input" style="width:auto" :max="todayStr" />
      <button class="btn" @click="all('present')"><Icon name="done_all" />All present</button>
    </PageHead>
    <div class="card">
      <div v-if="loading" style="padding:16px"><div v-for="i in 6" :key="i" class="skeleton" style="height:40px;margin-bottom:8px" /></div>
      <div v-else-if="error" style="padding:16px" class="muted">{{ error }}</div>
      <table v-else class="data">
        <thead><tr><th>Name</th><th>Designation</th><th>Status</th><th>In</th><th>Out</th></tr></thead>
        <tbody>
          <tr v-for="r in rows" :key="r.user_id">
            <td><b>{{ r.name }}</b></td><td class="muted">{{ r.designation }}</td>
            <td><select v-model="r.status" class="input sm" style="width:130px"><option v-for="s in STATUSES" :key="s" :value="s">{{ humanize(s) }}</option></select></td>
            <td><input v-model="r.in_time" type="time" class="input sm" style="width:120px" /></td>
            <td><input v-model="r.out_time" type="time" class="input sm" style="width:120px" /></td>
          </tr>
        </tbody>
      </table>
      <div class="foot"><span v-if="already" class="badge amber">Already marked — saving updates it</span><span class="grow" /><button class="btn primary" :disabled="saving" @click="save"><Icon name="save" />Save</button></div>
    </div>
  </div>
</template>
<script setup>
import { ref, watch } from 'vue';
import PageHead from '../../components/PageHead.vue';
import Icon from '../../components/Icon.vue';
import { get, post } from '../../api/client';
import { toast, toastError } from '../../ui/ui';
import { today, humanize } from '../../ui/format';
const STATUSES = ['present', 'absent', 'late', 'half_day', 'leave'];
const todayStr = today(); const date = ref(todayStr);
const rows = ref([]), loading = ref(false), saving = ref(false), error = ref(''), already = ref(false);
async function load() {
  loading.value = true; error.value = '';
  try { const r = await get('attendance/staff', { date: date.value }); already.value = r.data.already_marked; rows.value = (r.data.rows || []).map(x => ({ ...x, status: x.status || 'present', in_time: (x.in_time || '').slice(0, 5), out_time: (x.out_time || '').slice(0, 5) })); }
  catch (e) { error.value = e.message; }
  loading.value = false;
}
watch(date, load, { immediate: true });
const all = (s) => rows.value.forEach(r => (r.status = s));
async function save() {
  saving.value = true;
  try { const r = await post('attendance/staff', { date: date.value, records: rows.value.map(x => ({ user_id: x.user_id, status: x.status, in_time: x.in_time || null, out_time: x.out_time || null })) }); toast(r.message || 'Saved'); already.value = true; }
  catch (e) { toastError(e); }
  saving.value = false;
}
</script>
<style scoped>.foot { display: flex; align-items: center; gap: 10px; padding: 14px 16px; border-top: 1px solid var(--border); }</style>
