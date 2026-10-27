<template>
  <div class="page">
    <PageHead title="Audit log" sub="Every change in the system: who, when, and what changed (old → new). This log can never be edited." icon="history" color="#64748b">
      <button class="btn" @click="exportIt"><Icon name="download" />Export</button>
    </PageHead>
    <div class="card pad filters">
      <select v-model="f.module" class="input sm"><option value="">All sections</option><option v-for="m in app.moduleList" :key="m.key" :value="m.key">{{ m.title }}</option></select>
      <select v-model="f.action" class="input sm"><option value="">All actions</option><option v-for="a in ['created', 'updated', 'deleted', 'cancelled', 'locked', 'action', 'login']" :key="a" :value="a">{{ humanize(a) }}</option></select>
      <RefPicker v-model="f.user_id" :ref-info="{ module: 'staff', label_field: 'name' }" placeholder="Any user" sm style="width:200px" />
      <label class="small muted">From</label><input v-model="f.from" type="date" class="input sm" />
      <label class="small muted">To</label><input v-model="f.to" type="date" class="input sm" />
    </div>
    <div class="card pad" style="margin-top:14px">
      <HistoryList :rows="rows" :loading="loading" :error="error" show-record />
      <div class="row" style="margin-top:12px"><span class="small muted">{{ total }} entries</span><span class="grow" />
        <button class="btn sm icon" :disabled="page <= 1" @click="page--"><Icon name="chevron_left" /></button><span class="small">Page {{ page }} / {{ last }}</span>
        <button class="btn sm icon" :disabled="page >= last" @click="page++"><Icon name="chevron_right" /></button></div>
    </div>
  </div>
</template>
<script setup>
import { ref, reactive, watch } from 'vue';
import PageHead from '../../components/PageHead.vue';
import Icon from '../../components/Icon.vue';
import HistoryList from '../generic/HistoryList.vue';
import RefPicker from '../generic/RefPicker.vue';
import { get } from '../../api/client';
import { app } from '../../state';
import { downloadFile } from '../../ui/files';
import { humanize } from '../../ui/format';
const f = reactive({ module: '', action: '', user_id: null, from: '', to: '' });
const rows = ref([]), loading = ref(false), error = ref(''), page = ref(1), last = ref(1), total = ref(0);
async function load() {
  loading.value = true; error.value = '';
  try { const r = await get('audit', { ...f, page: page.value, per_page: 30 }); rows.value = r.data || []; last.value = r.meta?.last_page || 1; total.value = r.meta?.total ?? rows.value.length; }
  catch (e) { error.value = e.message; rows.value = []; }
  loading.value = false;
}
watch(f, () => { page.value = 1; load(); }, { deep: true }); watch(page, load); load();
const exportIt = () => downloadFile('export/audit', { format: 'xlsx', filter: Object.fromEntries(Object.entries(f).filter(([, v]) => v)) }, { filename: 'Audit_log.xlsx' });
</script>
<style scoped>.filters { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; } .filters select, .filters input[type=date] { width: auto; }</style>
