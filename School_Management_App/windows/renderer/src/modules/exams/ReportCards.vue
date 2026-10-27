<template>
  <div class="page">
    <PageHead title="Report cards" sub="Marksheets with percentage, CBSE grade and rank. Print or save as PDF." icon="grading" color="#ec4899">
      <select v-model.number="examId" class="input" style="width:auto"><option :value="null">Latest exam</option><option v-for="e in exams" :key="e.id" :value="e.id">{{ e.name }}</option></select>
      <ClassSectionPicker v-model="sectionId" />
      <button v-if="examId && sectionId" class="btn" @click="openPdf('admit-cards', null, { exam_id: examId, section_id: sectionId }, 'Admit_cards')"><Icon name="confirmation_number" />Admit cards (class)</button>
    </PageHead>
    <div class="layout">
      <div class="card list">
        <div v-if="!sectionId" class="muted p">Choose a class and section.</div>
        <div v-for="s in students" :key="s.id" class="st" :class="{ on: sel?.id === s.id }" @click="pick(s)"><span class="roll">{{ s.roll_no }}</span><div><b>{{ s.name }}</b><div class="small faint">{{ s.admission_no }}</div></div></div>
      </div>
      <div class="card rc">
        <EmptyState v-if="!sel" icon="grading" title="Pick a student" text="Their report card shows here." />
        <div v-else-if="loading" style="padding:20px"><div class="skeleton" style="height:360px" /></div>
        <div v-else-if="error" class="p"><EmptyState icon="info" :title="error" /></div>
        <div v-else-if="card" class="sheet fade-up">
          <div class="sh">
            <div><div class="small muted">{{ app.school?.name }}</div><h2>{{ card.student?.name || sel.name }}</h2><div class="muted small">{{ card.student?.class_label || sel.class_label }} · Roll {{ card.student?.roll_no ?? sel.roll_no }} · {{ card.exam?.name }}</div></div>
            <span class="grow" />
            <button class="btn" @click="printPdf('report-card', sel.id, q(), 'Report card')"><Icon name="print" />Print</button>
            <button class="btn primary" @click="openPdf('report-card', sel.id, q(), 'Report_card_' + (sel.admission_no || sel.id))"><Icon name="picture_as_pdf" />PDF</button>
          </div>
          <div class="sum stagger">
            <div><span>Total</span><b>{{ card.total }} / {{ card.max_total }}</b></div>
            <div><span>Percentage</span><b>{{ card.percentage }}%</b></div>
            <div><span>Grade</span><b>{{ card.grade || '—' }}</b></div>
            <div><span>Rank</span><b>{{ card.rank ? card.rank + ' / ' + card.class_strength : '—' }}</b></div>
            <div><span>Result</span><b :class="card.result === 'Pass' ? 'ok' : 'warn'">{{ card.result }}</b></div>
          </div>
          <table class="data"><thead><tr><th>Subject</th><th class="right">Marks</th><th class="right">Max</th><th>Grade</th><th>Remark</th></tr></thead>
            <tbody><tr v-for="s in card.subjects" :key="s.subject"><td><b>{{ s.subject }}</b></td><td class="right">{{ s.is_absent ? 'AB' : s.marks_obtained }}</td><td class="right muted">{{ s.max_marks }}</td><td><span class="badge indigo">{{ s.grade }}</span></td><td class="muted">{{ s.remark }}</td></tr></tbody>
          </table>
          <div class="sign small muted">Principal: <b>{{ app.school?.principal_name }}</b></div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, watch, onMounted } from 'vue';
import PageHead from '../../components/PageHead.vue';
import Icon from '../../components/Icon.vue';
import EmptyState from '../../components/EmptyState.vue';
import ClassSectionPicker from '../../components/ClassSectionPicker.vue';
import { get } from '../../api/client';
import { app } from '../../state';
import { openPdf, printPdf } from '../../ui/files';
const exams = ref([]), examId = ref(null), sectionId = ref(Number(localStorage.getItem('dav.att.section')) || null), students = ref([]), sel = ref(null), card = ref(null), loading = ref(false), error = ref('');
const q = () => (examId.value ? { exam_id: examId.value } : {});
onMounted(async () => { try { exams.value = (await get('exams')).data || []; } catch (_) {} });
watch(sectionId, async (v) => { sel.value = null; card.value = null; if (!v) { students.value = []; return; } try { students.value = (await get('students', { section_id: v, per_page: 200 })).data || []; } catch (_) { students.value = []; } }, { immediate: true });
watch(examId, () => sel.value && pick(sel.value));
async function pick(s) {
  sel.value = s; loading.value = true; error.value = ''; card.value = null;
  try { card.value = (await get(`students/${s.id}/report-card`, q())).data; } catch (e) { error.value = e.message; }
  loading.value = false;
}
</script>
<style scoped>
.layout { display: grid; grid-template-columns: 300px 1fr; gap: 16px; align-items: start; }
.list { max-height: calc(100vh - 210px); overflow: auto; padding: 6px; }
.st { display: flex; gap: 10px; align-items: center; padding: 9px 10px; border-radius: 10px; cursor: pointer; transition: background .12s; }
.st:hover { background: var(--panel-2); } .st.on { background: var(--primary-50); }
.roll { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; background: #f1f5f9; font-size: 12px; font-weight: 700; color: var(--muted); }
.p { padding: 16px; }
.sheet { padding: 20px; }
.sh { display: flex; gap: 10px; align-items: center; margin-bottom: 16px; }
.sum { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 16px; }
.sum div { padding: 12px; border-radius: 12px; background: var(--panel-2); border: 1px solid var(--border); display: flex; flex-direction: column; gap: 4px; }
.sum span { font-size: 12px; color: var(--muted); } .sum b { font-size: 18px; } .ok { color: var(--success); } .warn { color: var(--warning); }
.sign { text-align: right; margin-top: 18px; }
</style>
