<template>
  <div class="page">
    <PageHead title="Attendance report" sub="Month view for a section, with totals. Export to Excel or PDF." icon="calendar_month" color="#0ea5e9">
      <ClassSectionPicker v-model="sectionId" />
      <input v-model="month" type="month" class="input" style="width:auto" />
      <button class="btn" :disabled="!sectionId" @click="pdf"><Icon name="picture_as_pdf" />PDF</button>
      <button class="btn" :disabled="!sectionId" @click="excel"><Icon name="table_view" />Excel</button>
    </PageHead>
    <div v-if="daily.length" class="card pad daily fade-up">
      <div class="row"><h3>Today across the school</h3><span class="grow" /><span class="muted small">{{ todayText }}</span></div>
      <div class="chips"><div v-for="d in daily" :key="d.section_id" class="dchip" :class="{ un: !d.marked }" :title="d.marked ? '' : 'Not marked yet'"><b>{{ d.class_label }}</b><span>{{ d.marked ? d.present + '/' + d.strength : '—' }}</span></div></div>
    </div>
    <div v-if="!sectionId" class="card"><EmptyState icon="calendar_month" title="Choose a class and section" text="You'll see every student's attendance for each day of the month." /></div>
    <div v-else class="card">
      <div v-if="loading" class="pad"><div class="skeleton" style="height:300px" /></div>
      <div v-else-if="error" class="pad muted">{{ error }}</div>
      <div v-else class="table-wrap">
        <table class="data grid-t">
          <thead><tr><th class="sticky">Student</th><th v-for="d in days" :key="d" class="c" :class="{ sun: isSunday(d) }">{{ d }}</th><th class="c">P</th><th class="c">A</th><th class="c">%</th></tr></thead>
          <tbody>
            <tr v-for="s in students" :key="s.student_id || s.id">
              <td class="sticky"><b>{{ s.roll_no ? s.roll_no + '. ' : '' }}{{ s.name }}</b></td>
              <td v-for="d in days" :key="d" class="c" :class="{ sun: isSunday(d) }"><span :class="['m', code(s, d)]">{{ letter(code(s, d)) }}</span></td>
              <td class="c b">{{ s.totals?.present ?? s.present ?? count(s, 'present') }}</td>
              <td class="c b">{{ s.totals?.absent ?? s.absent ?? count(s, 'absent') }}</td>
              <td class="c b">{{ pct(s) }}</td>
            </tr>
          </tbody>
        </table>
        <div class="legend small muted"><span class="m present">P</span>Present <span class="m absent">A</span>Absent <span class="m late">L</span>Late <span class="m half_day">H</span>Half day <span class="m leave">LV</span>Leave</div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import PageHead from '../../components/PageHead.vue';
import Icon from '../../components/Icon.vue';
import EmptyState from '../../components/EmptyState.vue';
import ClassSectionPicker from '../../components/ClassSectionPicker.vue';
import { get } from '../../api/client';
import { openPdf, downloadFile } from '../../ui/files';
import { thisMonth, today, date } from '../../ui/format';
const sectionId = ref(Number(localStorage.getItem('dav.att.section')) || null), month = ref(thisMonth());
const data = ref(null), loading = ref(false), error = ref(''), daily = ref([]);
const todayText = date(today());
const days = computed(() => { const [y, m] = month.value.split('-').map(Number); return Array.from({ length: new Date(y, m, 0).getDate() }, (_, i) => i + 1); });
const students = computed(() => data.value?.students || data.value?.rows || (Array.isArray(data.value) ? data.value : []));
const isSunday = (d) => new Date(month.value + '-' + String(d).padStart(2, '0')).getDay() === 0;
function code(s, d) {
  const key = month.value + '-' + String(d).padStart(2, '0');
  const days = s.days || s.attendance || {};
  if (Array.isArray(days)) { const f = days.find(x => (x.date || '').slice(0, 10) === key || x.day === d); return f?.status || ''; }
  return days[key] || days[d] || days[String(d)] || '';
}
const letter = (c) => ({ present: 'P', absent: 'A', late: 'L', half_day: 'H', leave: 'LV', holiday: '·' }[c] || '');
const count = (s, st) => days.value.filter(d => code(s, d) === st).length;
function pct(s) {
  if (s.totals?.percentage != null) return s.totals.percentage + '%'; if (s.percentage != null) return s.percentage + '%';
  const marked = days.value.filter(d => code(s, d)).length; if (!marked) return '—';
  return Math.round((count(s, 'present') + count(s, 'late') + count(s, 'half_day') / 2) * 100 / marked) + '%';
}
async function load() {
  if (!sectionId.value) return;
  loading.value = true; error.value = '';
  try { data.value = (await get('attendance/report', { section_id: sectionId.value, month: month.value })).data; }
  catch (e) { error.value = e.message; data.value = null; }
  loading.value = false;
}
watch([sectionId, month], load, { immediate: true });
onMounted(async () => {
  try { const r = await get('attendance/daily', { date: today() }); daily.value = r.data?.sections || r.data || []; }
  catch (_) { try { daily.value = (await get('attendance/class-summary')).data || []; } catch (_) {} }
});
const pdf = () => openPdf('attendance', sectionId.value, { month: month.value }, 'Attendance_' + month.value);
const excel = () => downloadFile('export/student_attendance', { format: 'xlsx', filter: { section_id: sectionId.value, month: month.value } }, { filename: 'Attendance_' + month.value + '.xlsx' });
</script>
<style scoped>
.pad { padding: 16px; }
.daily { margin-bottom: 16px; }
.chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
.dchip { display: flex; flex-direction: column; padding: 8px 12px; border-radius: 11px; background: var(--success-50); color: #065f46; font-size: 12px; min-width: 76px; }
.dchip.un { background: #f1f5f9; color: var(--muted); }
.grid-t th, .grid-t td { padding: 7px 5px; font-size: 12.5px; }
.grid-t .c { text-align: center; min-width: 26px; } .grid-t .b { font-weight: 700; }
.sticky { position: sticky; left: 0; background: #fff; z-index: 2; min-width: 200px; padding-left: 14px !important; }
th.sticky { background: var(--panel-2); z-index: 3; }
.sun { background: #fafafa; }
.m { display: inline-grid; place-items: center; min-width: 20px; height: 20px; border-radius: 6px; font-size: 10.5px; font-weight: 700; }
.m.present { background: var(--success-50); color: var(--success); } .m.absent { background: var(--danger-50); color: var(--danger); } .m.late { background: #e0f2fe; color: #0369a1; }
.m.half_day { background: var(--warning-50); color: var(--warning); } .m.leave { background: #ede9fe; color: #6d28d9; }
.legend { padding: 12px 16px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
</style>
