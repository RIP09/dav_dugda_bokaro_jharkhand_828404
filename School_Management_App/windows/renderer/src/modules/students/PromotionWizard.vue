<template>
  <div class="page">
    <PageHead title="Promotion & new session" sub="Move students to the next class at the end of the session. Each student's class history is kept permanently." icon="trending_up" color="#10b981" />
    <div class="steps"><div v-for="(s, i) in STEPS" :key="s" class="step" :class="{ on: step === i, done: step > i }"><span>{{ step > i ? '✓' : i + 1 }}</span>{{ s }}</div></div>
    <div class="card pad fade-up" :key="step">
      <template v-if="step === 0">
        <h3>Choose sessions</h3>
        <div class="form-grid" style="margin-top:12px;max-width:640px">
          <div class="field"><label>From session</label><select v-model.number="fromYear" class="input"><option v-for="y in years" :key="y.id" :value="y.id">{{ y.name || y.title }}</option></select></div>
          <div class="field"><label>To session</label><select v-model.number="toYear" class="input"><option v-for="y in years" :key="y.id" :value="y.id">{{ y.name || y.title }}</option></select></div>
        </div>
        <p class="small muted">Need the next session first? Add it under Academics → Academic years.</p>
      </template>
      <template v-else-if="step === 1">
        <h3>Class mapping</h3><p class="small muted">Each class moves to the class on the right. Choose “Pass out / Leave school” for the final class.</p>
        <table class="data" style="max-width:640px"><thead><tr><th>From class</th><th>To class</th></tr></thead>
          <tbody><tr v-for="m in mappings" :key="m.from_class_id"><td><b>{{ className(m.from_class_id) }}</b></td>
            <td><select v-model="m.to_class_id" class="input sm"><option :value="null">Pass out / Leave school</option><option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option></select></td></tr></tbody></table>
      </template>
      <template v-else-if="step === 2">
        <h3>Students who stay back (optional)</h3><p class="small muted">Tick students who should NOT be promoted (detained).</p>
        <ClassSectionPicker v-model="exSection" />
        <div class="ex">
          <label v-for="s in exStudents" :key="s.id" class="exs"><input v-model="exclude" type="checkbox" :value="s.id" />{{ s.roll_no }}. {{ s.name }}</label>
        </div>
        <p class="small">{{ exclude.length }} student(s) will stay in their class.</p>
      </template>
      <template v-else>
        <h3>Confirm</h3>
        <p>Promote students from <b>{{ yearName(fromYear) }}</b> to <b>{{ yearName(toYear) }}</b> using {{ mappings.length }} class mappings, keeping back {{ exclude.length }} student(s).</p>
        <div class="lockbar"><Icon name="lock" /><div>Promotion records are permanent and cannot be edited later.</div></div>
        <p v-if="result" class="ok">{{ result }}</p>
      </template>
      <div class="nav">
        <button class="btn" :disabled="step === 0" @click="step--">Back</button><span class="grow" />
        <button v-if="step < 3" class="btn primary" :disabled="!fromYear || !toYear || fromYear === toYear" @click="next">Next</button>
        <button v-else class="btn success" :disabled="busy || !!result" @click="run"><Icon :name="busy ? 'progress_activity' : 'rocket_launch'" :class="{ spin: busy }" />Promote now</button>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, watch, onMounted } from 'vue';
import PageHead from '../../components/PageHead.vue';
import Icon from '../../components/Icon.vue';
import ClassSectionPicker from '../../components/ClassSectionPicker.vue';
import { get, post } from '../../api/client';
import { toast, toastError, confirmBox } from '../../ui/ui';
const STEPS = ['Sessions', 'Class mapping', 'Stay back', 'Confirm'];
const step = ref(0), years = ref([]), classes = ref([]), fromYear = ref(null), toYear = ref(null), mappings = ref([]), exclude = ref([]), exSection = ref(null), exStudents = ref([]), busy = ref(false), result = ref('');
const className = (id) => classes.value.find(c => c.id === id)?.name || id;
const yearName = (id) => { const y = years.value.find(y => y.id === id); return y?.name || y?.title || id; };
onMounted(async () => {
  const [y, c] = await Promise.allSettled([get('academic-years'), get('classes')]);
  years.value = y.value?.data || []; classes.value = (c.value?.data || []).sort((a, b) => (a.numeric_order ?? a.id) - (b.numeric_order ?? b.id));
  const cur = years.value.find(x => x.is_current); fromYear.value = cur?.id || years.value[0]?.id; toYear.value = years.value.find(x => x.id !== fromYear.value && (!cur || (x.start_date || '') > (cur.start_date || '')))?.id || null;
  mappings.value = classes.value.map((c, i) => ({ from_class_id: c.id, to_class_id: classes.value[i + 1]?.id ?? null }));
});
watch(exSection, async (v) => { exStudents.value = v ? ((await get('students', { section_id: v, per_page: 200 }).catch(() => ({ data: [] }))).data || []) : []; });
function next() { step.value++; }
async function run() {
  if (!(await confirmBox({ title: 'Promote students now?', message: 'This moves students to their new classes for the new session.', ok: 'Promote' }))) return;
  busy.value = true;
  try { const r = await post('promotion/run', { from_academic_year_id: fromYear.value, to_academic_year_id: toYear.value, mappings: mappings.value, exclude_student_ids: exclude.value }); result.value = r.message || 'Promotion done'; toast(result.value); }
  catch (e) { toastError(e); }
  busy.value = false;
}
</script>
<style scoped>
.steps { display: flex; gap: 8px; margin-bottom: 16px; }
.step { flex: 1; display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: 12px; background: #fff; border: 1px solid var(--border); color: var(--muted); font-weight: 600; transition: all .25s var(--ease); }
.step span { width: 26px; height: 26px; border-radius: 50%; display: grid; place-items: center; background: #f1f5f9; font-size: 12.5px; }
.step.on { border-color: var(--primary); color: var(--primary); box-shadow: 0 0 0 4px rgba(99,102,241,.1); } .step.on span { background: var(--primary); color: #fff; }
.step.done span { background: var(--success); color: #fff; }
.nav { display: flex; margin-top: 22px; padding-top: 14px; border-top: 1px solid var(--border); }
.ex { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 6px; margin: 14px 0; }
.exs { display: flex; gap: 8px; align-items: center; padding: 6px 8px; border-radius: 8px; background: var(--panel-2); cursor: pointer; }
.ok { color: var(--success); font-weight: 650; }
</style>
