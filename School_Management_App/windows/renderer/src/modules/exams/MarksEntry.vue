<template>
  <div class="page">
    <PageHead title="Marks entry" sub="Choose exam, class and subject, type the marks, and save. Grades are worked out automatically (CBSE scale)." icon="edit_note" color="#a855f7">
      <select v-model.number="examId" class="input" style="width:auto"><option :value="null">Choose exam</option><option v-for="e in exams" :key="e.id" :value="e.id">{{ e.name }}{{ e.term ? ' (' + e.term + ')' : '' }}</option></select>
      <ClassSectionPicker v-model="sectionId" @section="s => (classId = s.class_id)" />
      <select v-model.number="subjectId" class="input" style="width:auto"><option :value="null">Subject</option><option v-for="s in classSubjects" :key="s.id" :value="s.id">{{ s.name }}</option></select>
    </PageHead>
    <div v-if="locked" class="lockbar" style="margin-bottom:14px"><Icon name="lock" /><div><b>Results for this exam are locked.</b> Marks can no longer be changed. {{ exam?._lock_reason || '' }}</div></div>
    <div v-if="!ready" class="card"><EmptyState icon="edit_note" title="Choose exam, class and subject" text="The marks sheet for the class appears here." /></div>
    <div v-else class="card">
      <div class="bar"><span>Maximum marks</span><input v-model.number="maxMarks" type="number" class="input sm" style="width:90px" :disabled="locked" /><span class="muted small">Pass marks {{ passMarks }}</span><span class="grow" />
        <span class="small muted">Average {{ avg }}</span></div>
      <div v-if="loading" style="padding:16px"><div v-for="i in 6" :key="i" class="skeleton" style="height:40px;margin-bottom:8px" /></div>
      <div v-else-if="error" style="padding:16px" class="muted">{{ error }}</div>
      <table v-else class="data">
        <thead><tr><th style="width:70px">Roll</th><th>Student</th><th style="width:140px">Marks</th><th style="width:90px">Absent</th><th style="width:80px">Grade</th><th>Remark</th></tr></thead>
        <tbody>
          <tr v-for="(r, i) in rows" :key="r.student_id" :class="{ fail: isFail(r) }">
            <td class="mono">{{ r.roll_no }}</td><td><b>{{ r.name }}</b></td>
            <td><input :ref="el => (inputs[i] = el)" v-model.number="r.marks_obtained" type="number" min="0" :max="maxMarks" class="input sm" :class="{ invalid: r.marks_obtained > maxMarks }" :disabled="locked || r.is_absent" @keydown.enter.prevent="inputs[i + 1]?.focus()" /></td>
            <td><button class="switch" :class="{ on: r.is_absent }" :disabled="locked" @click="r.is_absent = !r.is_absent" /></td>
            <td><span class="badge" :class="gradeColor(r)">{{ gradeOf(r) }}</span></td>
            <td><input v-model="r.remark" class="input sm" :disabled="locked" /></td>
          </tr>
        </tbody>
      </table>
      <div class="foot"><span class="small muted">Press Enter to jump to the next student.</span><span class="grow" /><button class="btn primary" :disabled="saving || locked || !rows.length" @click="save"><Icon :name="locked ? 'lock' : 'save'" />Save marks</button></div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import PageHead from '../../components/PageHead.vue';
import Icon from '../../components/Icon.vue';
import EmptyState from '../../components/EmptyState.vue';
import ClassSectionPicker from '../../components/ClassSectionPicker.vue';
import { get, post } from '../../api/client';
import { toast, toastError } from '../../ui/ui';
const exams = ref([]), subjects = ref([]), grades = ref([]), examId = ref(null), sectionId = ref(null), classId = ref(null), subjectId = ref(null);
const rows = ref([]), maxMarks = ref(100), passMarks = ref(33), loading = ref(false), saving = ref(false), error = ref(''), inputs = ref([]), sheetLocked = ref(false);
const exam = computed(() => exams.value.find(e => e.id === examId.value));
const locked = computed(() => !!(exam.value?._locked || exam.value?.results_locked || exam.value?.is_locked || sheetLocked.value));
const classSubjects = computed(() => subjects.value.filter(s => !s.class_id || !classId.value || s.class_id === classId.value));
const ready = computed(() => examId.value && sectionId.value && subjectId.value);
const avg = computed(() => { const v = rows.value.filter(r => !r.is_absent && r.marks_obtained != null && r.marks_obtained !== ''); return v.length ? (v.reduce((a, r) => a + Number(r.marks_obtained), 0) / v.length).toFixed(1) : '—'; });
onMounted(async () => {
  const [e, s, g] = await Promise.allSettled([get('exams'), get('subjects'), get('grades')]);
  exams.value = e.value?.data || []; subjects.value = s.value?.data || []; grades.value = g.value?.data || [];
  try { const r = await get('records/exams', { per_page: 100 }); const m = Object.fromEntries((r.data || []).map(x => [x.id, x])); exams.value = exams.value.map(x => ({ ...x, ...(m[x.id] ? { _locked: m[x.id]._locked, _lock_reason: m[x.id]._lock_reason, results_locked: m[x.id].results_locked } : {}) })); } catch (_) {}
});
async function load() {
  if (!ready.value) return;
  loading.value = true; error.value = ''; sheetLocked.value = false;
  try {
    const r = await get('marks', { exam_id: examId.value, section_id: sectionId.value, subject_id: subjectId.value });
    maxMarks.value = r.data.max_marks || 100; passMarks.value = r.data.pass_marks || 33; sheetLocked.value = !!(r.data.locked || r.data.results_locked);
    rows.value = (r.data.rows || []).map(x => ({ ...x, remark: x.remark || '' }));
  } catch (e) { error.value = e.message; rows.value = []; }
  loading.value = false;
}
watch([examId, sectionId, subjectId], load);
function gradeOf(r) {
  if (r.is_absent) return 'AB'; if (r.marks_obtained === null || r.marks_obtained === '' || r.marks_obtained === undefined) return '—';
  const p = r.marks_obtained * 100 / (maxMarks.value || 100);
  const g = [...grades.value].sort((a, b) => b.min_percent - a.min_percent).find(x => p >= Number(x.min_percent));
  if (g) return g.name;
  return p >= 91 ? 'A1' : p >= 81 ? 'A2' : p >= 71 ? 'B1' : p >= 61 ? 'B2' : p >= 51 ? 'C1' : p >= 41 ? 'C2' : p >= 33 ? 'D' : 'E';
}
const isFail = (r) => r.is_absent || (r.marks_obtained !== null && r.marks_obtained !== '' && r.marks_obtained !== undefined && r.marks_obtained * 100 / maxMarks.value < 33);
const gradeColor = (r) => (isFail(r) ? 'red' : /^A/.test(gradeOf(r)) ? 'green' : 'indigo');
async function save() {
  if (rows.value.some(r => r.marks_obtained > maxMarks.value)) { toast('Some marks are more than the maximum.', 'error'); return; }
  saving.value = true;
  try {
    const r = await post('marks', { exam_id: examId.value, section_id: sectionId.value, subject_id: subjectId.value, max_marks: maxMarks.value,
      records: rows.value.map(x => ({ student_id: x.student_id, marks_obtained: x.is_absent ? null : (x.marks_obtained === '' ? null : x.marks_obtained), is_absent: !!x.is_absent, remark: x.remark || null })) });
    toast(r.message || 'Marks saved');
  } catch (e) { toastError(e); if (e.status === 423) sheetLocked.value = true; }
  saving.value = false;
}
</script>
<style scoped>
.bar { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-bottom: 1px solid var(--border); font-weight: 550; }
tr.fail td { background: #fff7f7; }
.foot { display: flex; align-items: center; padding: 14px 16px; border-top: 1px solid var(--border); }
</style>
