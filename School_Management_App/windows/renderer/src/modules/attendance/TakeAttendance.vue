<template>
  <div class="page">
    <PageHead title="Take attendance" sub="Pick a class and date, mark each student, then save." icon="fact_check" color="#10b981">
      <ClassSectionPicker v-model="sectionId" />
      <input v-model="date" type="date" class="input" style="width:auto" :max="todayStr" />
    </PageHead>
    <div v-if="!sectionId" class="card"><EmptyState icon="fact_check" title="Choose a class and section" text="Attendance for the chosen day appears here. Everyone starts as Present — just tap the absentees." /></div>
    <div v-else class="card">
      <div class="bar">
        <span v-if="sheet?.already_marked" class="badge amber"><Icon name="history" />Already marked — saving updates it (recorded in history)</span>
        <span class="grow" />
        <span class="cnt green">Present {{ counts.present + counts.late }}</span><span class="cnt red">Absent {{ counts.absent }}</span><span class="cnt amber">Leave {{ counts.leave }}</span>
        <button class="btn sm" @click="markAll('present')"><Icon name="done_all" />Mark all present</button>
      </div>
      <div v-if="loading" class="pad"><div v-for="i in 6" :key="i" class="skeleton" style="height:42px;margin-bottom:8px" /></div>
      <div v-else-if="error" class="pad err">{{ error }}</div>
      <table v-else class="data">
        <thead><tr><th style="width:70px">Roll</th><th>Student</th><th>Status</th><th>Remark</th></tr></thead>
        <tbody>
          <tr v-for="r in rows" :key="r.student_id" :class="'st-' + r.status">
            <td class="mono">{{ r.roll_no }}</td>
            <td><b>{{ r.name }}</b><div class="small faint">{{ r.admission_no }}</div></td>
            <td><div class="seg"><button v-for="s in STATUSES" :key="s.v" :class="['segb', s.v, { on: r.status === s.v }]" @click="r.status = s.v">{{ s.l }}</button></div></td>
            <td><input v-model="r.remark" class="input sm" placeholder="Optional" /></td>
          </tr>
        </tbody>
      </table>
      <div class="foot"><span class="muted small">{{ rows.length }} students · {{ dateText }}</span><span class="grow" />
        <button class="btn primary" :disabled="saving || !rows.length" @click="save"><Icon :name="saving ? 'progress_activity' : 'save'" :class="{ spin: saving }" />Save attendance</button></div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, watch } from 'vue';
import PageHead from '../../components/PageHead.vue';
import Icon from '../../components/Icon.vue';
import EmptyState from '../../components/EmptyState.vue';
import ClassSectionPicker from '../../components/ClassSectionPicker.vue';
import { get, post, session } from '../../api/client';
import { toast, toastError } from '../../ui/ui';
import { today, date as fmtDate } from '../../ui/format';
const STATUSES = [{ v: 'present', l: 'Present' }, { v: 'absent', l: 'Absent' }, { v: 'late', l: 'Late' }, { v: 'half_day', l: 'Half day' }, { v: 'leave', l: 'Leave' }];
const todayStr = today();
const sectionId = ref(session.user?.role === 'teacher' ? null : (Number(localStorage.getItem('dav.att.section')) || null)), date = ref(todayStr);
const sheet = ref(null), rows = ref([]), loading = ref(false), saving = ref(false), error = ref('');
const counts = computed(() => rows.value.reduce((a, r) => { a[r.status] = (a[r.status] || 0) + 1; return a; }, { present: 0, absent: 0, late: 0, leave: 0, half_day: 0 }));
const dateText = computed(() => fmtDate(date.value));
async function load() {
  if (!sectionId.value) return;
  localStorage.setItem('dav.att.section', sectionId.value);
  loading.value = true; error.value = '';
  try {
    const r = await get('attendance/students', { section_id: sectionId.value, date: date.value });
    sheet.value = r.data; rows.value = (r.data.rows || []).map(x => ({ ...x, status: x.status || 'present', remark: x.remark || '' }));
  } catch (e) { error.value = e.message; rows.value = []; }
  loading.value = false;
}
watch([sectionId, date], load, { immediate: true });
function markAll(s) { rows.value.forEach(r => (r.status = s)); }
async function save() {
  saving.value = true;
  try {
    const r = await post('attendance/students', { section_id: sectionId.value, date: date.value, records: rows.value.map(x => ({ student_id: x.student_id, status: x.status, remark: x.remark || null })) });
    toast(r.message || 'Attendance saved'); sheet.value = { ...sheet.value, already_marked: true };
  } catch (e) { toastError(e); }
  saving.value = false;
}
</script>
<style scoped>
.bar { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-bottom: 1px solid var(--border); flex-wrap: wrap; }
.cnt { font-size: 12.5px; font-weight: 650; padding: 4px 10px; border-radius: 8px; }
.cnt.green { background: var(--success-50); color: var(--success); } .cnt.red { background: var(--danger-50); color: var(--danger); } .cnt.amber { background: var(--warning-50); color: var(--warning); }
.seg { display: inline-flex; background: #f1f5f9; border-radius: 10px; padding: 3px; gap: 2px; }
.segb { border: 0; background: transparent; padding: 6px 11px; border-radius: 8px; font: inherit; font-size: 12.5px; font-weight: 600; color: var(--muted); cursor: pointer; transition: all .15s; }
.segb:hover { color: var(--text); }
.segb.on.present { background: var(--success); color: #fff; } .segb.on.absent { background: var(--danger); color: #fff; } .segb.on.late { background: #0ea5e9; color: #fff; }
.segb.on.half_day { background: var(--warning); color: #fff; } .segb.on.leave { background: #8b5cf6; color: #fff; }
tr.st-absent td { background: #fff5f6; }
.foot { display: flex; align-items: center; padding: 14px 16px; border-top: 1px solid var(--border); position: sticky; bottom: 0; background: #fff; border-radius: 0 0 var(--radius) var(--radius); }
.pad { padding: 16px; } .err { color: var(--danger); }
</style>
