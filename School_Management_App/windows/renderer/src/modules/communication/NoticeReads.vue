<template>
  <div>
    <div v-if="loading" class="skeleton" style="height:120px" />
    <div v-else-if="error" class="muted">{{ error }}</div>
    <template v-else>
      <div class="sum"><div><b>{{ data.read_count ?? reads.length }}</b><span>have read it</span></div><div v-if="data.total != null"><b>{{ data.total }}</b><span>should read it</span></div>
        <div v-if="data.total"><b>{{ Math.round((data.read_count ?? reads.length) * 100 / data.total) }}%</b><span>read</span></div></div>
      <table class="data"><thead><tr><th>Name</th><th>Role</th><th>Read at</th></tr></thead>
        <tbody><tr v-for="r in reads" :key="r.user_id || r.id"><td>{{ r.user_name || r.name }}</td><td class="muted">{{ r.role }}</td><td class="muted">{{ datetime(r.read_at) }}</td></tr></tbody></table>
      <p v-if="!reads.length" class="muted">Nobody has opened this notice yet.</p>
    </template>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { get } from '../../api/client';
import { datetime } from '../../ui/format';
const props = defineProps({ row: Object });
const data = ref({}), reads = ref([]), loading = ref(true), error = ref('');
onMounted(async () => {
  try { const r = await get(`notices/${props.row.id}/reads`); data.value = r.data || {}; reads.value = Array.isArray(r.data) ? r.data : (r.data?.reads || r.data?.readers || []); }
  catch (e) { error.value = e.message; }
  loading.value = false;
});
</script>
<style scoped>
.sum { display: flex; gap: 10px; margin-bottom: 14px; }
.sum div { flex: 1; padding: 12px; border-radius: 12px; background: var(--panel-2); border: 1px solid var(--border); display: flex; flex-direction: column; }
.sum b { font-size: 20px; } .sum span { font-size: 12px; color: var(--muted); }
</style>
