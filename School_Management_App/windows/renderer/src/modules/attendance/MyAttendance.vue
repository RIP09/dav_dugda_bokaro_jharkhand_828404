<template>
  <div class="page">
    <PageHead title="My attendance" :sub="data?.student ? data.student.name + ' · ' + (data.student.class_label || '') : 'Your attendance for the month'" icon="event_available" color="#10b981">
      <select v-if="children.length > 1" v-model.number="childId" class="input" style="width:auto"><option v-for="c in children" :key="c.id" :value="c.id">{{ c.name }}</option></select>
      <input v-model="month" type="month" class="input" style="width:auto" />
    </PageHead>
    <div v-if="loading" class="skeleton" style="height:300px" />
    <div v-else-if="error" class="card"><EmptyState icon="info" :title="error" /></div>
    <template v-else-if="data">
      <div class="grid stats stagger">
        <StatCard label="Attendance" :value="data.percentage + '%'" icon="percent" color="#10b981" />
        <StatCard label="Present" :value="data.present" icon="check_circle" color="#6366f1" />
        <StatCard label="Absent" :value="data.absent" icon="cancel" color="#ef4444" />
        <StatCard label="Leave" :value="data.leave" icon="event_busy" color="#a855f7" />
      </div>
      <div class="card cal fade-up">
        <div v-for="d in ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']" :key="d" class="wd">{{ d }}</div>
        <div v-for="c in cells" :key="c.key" class="cell" :class="[c.status, { out: !c.in }]"><b>{{ c.d }}</b><span v-if="c.status">{{ humanize(c.status) }}</span></div>
      </div>
    </template>
  </div>
</template>
<script setup>
import { ref, computed, watch } from 'vue';
import PageHead from '../../components/PageHead.vue';
import EmptyState from '../../components/EmptyState.vue';
import StatCard from '../../components/StatCard.vue';
import { get, session } from '../../api/client';
import { thisMonth, humanize } from '../../ui/format';
const month = ref(thisMonth()), data = ref(null), loading = ref(false), error = ref('');
const children = computed(() => session.user?.children || []);
const childId = ref(children.value[0]?.id || null);
const cells = computed(() => {
  const [y, m] = month.value.split('-').map(Number); const first = new Date(y, m - 1, 1); const pad = (first.getDay() + 6) % 7; const n = new Date(y, m, 0).getDate();
  const map = Object.fromEntries((data.value?.days || []).map(d => [String(d.date).slice(0, 10), d.status]));
  const out = []; for (let i = 0; i < pad; i++) out.push({ key: 'p' + i, in: false });
  for (let d = 1; d <= n; d++) { const k = `${month.value}-${String(d).padStart(2, '0')}`; out.push({ key: k, d, in: true, status: map[k] || '' }); }
  return out;
});
async function load() {
  loading.value = true; error.value = '';
  try { data.value = (await get('attendance/my', { month: month.value, student_id: childId.value || undefined })).data; } catch (e) { error.value = e.message; data.value = null; }
  loading.value = false;
}
watch([month, childId], load, { immediate: true });
</script>
<style scoped>
.stats { grid-template-columns: repeat(4, 1fr); margin-bottom: 16px; }
.cal { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; padding: 14px; }
.wd { font-size: 12px; font-weight: 700; color: var(--muted); text-align: center; text-transform: uppercase; }
.cell { min-height: 70px; border-radius: 12px; background: var(--panel-2); padding: 8px; display: flex; flex-direction: column; gap: 4px; font-size: 12px; }
.cell.out { background: transparent; }
.cell.present { background: var(--success-50); color: #065f46; } .cell.absent { background: var(--danger-50); color: #9f1239; } .cell.late { background: #e0f2fe; color: #075985; }
.cell.half_day { background: var(--warning-50); color: #92400e; } .cell.leave { background: #ede9fe; color: #5b21b6; }
</style>
