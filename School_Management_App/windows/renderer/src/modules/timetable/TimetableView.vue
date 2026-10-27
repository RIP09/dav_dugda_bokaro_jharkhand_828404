<template>
  <div class="page">
    <PageHead title="Timetable" sub="Weekly periods. Clashes for a class or a teacher are blocked automatically." icon="calendar_view_week" color="#6366f1">
      <div v-if="isStaff" class="seg"><button :class="{ on: mode === 'section' }" @click="mode = 'section'">By class</button><button :class="{ on: mode === 'mine' }" @click="mode = 'mine'">My periods</button></div>
      <ClassSectionPicker v-if="mode === 'section'" v-model="sectionId" />
      <button class="btn" @click="printPage"><Icon name="print" />Print</button>
      <button v-if="canEdit && mode === 'section' && sectionId" class="btn primary" @click="openAdd()"><Icon name="add" />Add period</button>
    </PageHead>
    <div v-if="mode === 'section' && !sectionId" class="card"><EmptyState icon="calendar_view_week" title="Choose a class and section" /></div>
    <div v-else class="card tt">
      <div v-if="loading" style="padding:16px"><div class="skeleton" style="height:340px" /></div>
      <div v-else class="week">
        <div v-for="d in DAYS" :key="d.n" class="day">
          <div class="dh">{{ d.l }}</div>
          <div v-for="e in byDay(d.n)" :key="e.id" class="period fade-up" :style="{ '--c': colorOf(e.subject_id) }">
            <div class="time">{{ hm(e.start_time) }} – {{ hm(e.end_time) }}</div>
            <div class="sub">{{ e.subject?.name || e.subject_name }}</div>
            <div class="small muted">{{ mode === 'mine' ? (e.class_label || ((e.school_class?.name || '') + ' - ' + (e.section?.name || ''))) : (e.teacher?.name || '') }}<span v-if="e.room"> · {{ e.room }}</span></div>
            <button v-if="canEdit && mode === 'section'" class="x" title="Remove period" @click="remove(e)"><Icon name="close" :size="15" /></button>
          </div>
          <button v-if="canEdit && mode === 'section'" class="addp" @click="openAdd(d.n)"><Icon name="add" :size="16" /></button>
          <div v-if="!byDay(d.n).length && !(canEdit && mode === 'section')" class="small faint none">No periods</div>
        </div>
      </div>
    </div>
    <Modal :open="adding" title="Add period" icon="add_alarm" :width="520" @close="adding = false">
      <div class="form-grid">
        <div class="field"><label>Day</label><select v-model.number="form.day" class="input"><option v-for="d in DAYS" :key="d.n" :value="d.n">{{ d.l }}</option></select></div>
        <div class="field"><label>Room</label><input v-model="form.room" class="input" placeholder="Optional" /></div>
        <div class="field"><label>Starts</label><input v-model="form.start_time" type="time" class="input" /></div>
        <div class="field"><label>Ends</label><input v-model="form.end_time" type="time" class="input" /></div>
        <div class="field span2"><label>Subject</label><select v-model.number="form.subject_id" class="input"><option :value="null">Choose</option><option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option></select></div>
        <div class="field span2"><label>Teacher</label><select v-model.number="form.teacher_id" class="input"><option :value="null">—</option><option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option></select></div>
      </div>
      <p v-if="formError" class="err small">{{ formError }}</p>
      <template #footer><button class="btn" @click="adding = false">Cancel</button><button class="btn primary" :disabled="!form.subject_id" @click="add">Add period</button></template>
    </Modal>
  </div>
</template>
<script setup>
import { ref, reactive, computed, watch } from 'vue';
import PageHead from '../../components/PageHead.vue';
import Icon from '../../components/Icon.vue';
import EmptyState from '../../components/EmptyState.vue';
import Modal from '../../components/Modal.vue';
import ClassSectionPicker from '../../components/ClassSectionPicker.vue';
import { get, post, del, session } from '../../api/client';
import { confirmBox, toast, toastError } from '../../ui/ui';
import { printPage } from '../../ui/files';
const DAYS = [{ n: 1, l: 'Monday' }, { n: 2, l: 'Tuesday' }, { n: 3, l: 'Wednesday' }, { n: 4, l: 'Thursday' }, { n: 5, l: 'Friday' }, { n: 6, l: 'Saturday' }];
const role = session.user?.role;
const isStaff = ['teacher', 'staff', 'librarian', 'accountant'].includes(role);
const canEdit = role === 'admin';
const mode = ref(role === 'teacher' ? 'mine' : 'section');
const sectionId = ref(Number(localStorage.getItem('dav.att.section')) || null);
const entries = ref([]), loading = ref(false), adding = ref(false), formError = ref(''), subjects = ref([]), teachers = ref([]);
const form = reactive({ day: 1, start_time: '08:00', end_time: '08:40', subject_id: null, teacher_id: null, room: '' });
const byDay = (n) => entries.value.filter(e => Number(e.day) === n).sort((a, b) => String(a.start_time).localeCompare(String(b.start_time)));
const hm = (t) => String(t || '').slice(0, 5);
const colors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#a855f7', '#ec4899', '#84cc16', '#f97316', '#14b8a6'];
const colorOf = (id) => colors[(Number(id) || 0) % colors.length];
async function load() {
  if (mode.value === 'section' && !sectionId.value) { entries.value = []; return; }
  loading.value = true;
  try { entries.value = (await get('timetable', mode.value === 'mine' ? { mine: 1 } : { section_id: sectionId.value })).data || []; } catch (e) { toastError(e); entries.value = []; }
  loading.value = false;
}
watch([mode, sectionId], load, { immediate: true });
async function openAdd(day) {
  formError.value = ''; if (day) form.day = day;
  const last = byDay(form.day).slice(-1)[0];
  if (last) { form.start_time = hm(last.end_time); const [h, m] = form.start_time.split(':').map(Number); const t = h * 60 + m + 40; form.end_time = String(Math.floor(t / 60)).padStart(2, '0') + ':' + String(t % 60).padStart(2, '0'); }
  adding.value = true;
  if (!subjects.value.length) { try { subjects.value = (await get('subjects')).data || []; } catch (_) {} }
  if (!teachers.value.length) { try { teachers.value = (await get('users', { role: 'teacher' })).data || []; } catch (_) {} }
}
async function add() {
  formError.value = '';
  try { await post('timetable', { ...form, section_id: sectionId.value, room: form.room || null }); toast('Period added'); adding.value = false; load(); }
  catch (e) { formError.value = e.errors ? Object.values(e.errors)[0][0] : e.message; }
}
async function remove(e) {
  if (!(await confirmBox({ title: 'Remove this period?', message: `${e.subject?.name || ''} ${hm(e.start_time)}–${hm(e.end_time)}`, ok: 'Remove', danger: true }))) return;
  try { await del('timetable/' + e.id); toast('Period removed'); load(); } catch (err) { toastError(err); }
}
</script>
<style scoped>
.seg { display: inline-flex; background: #eef0f6; border-radius: 10px; padding: 3px; }
.seg button { border: 0; background: transparent; padding: 7px 12px; border-radius: 8px; font: inherit; font-weight: 600; font-size: 13px; color: var(--muted); cursor: pointer; }
.seg button.on { background: #fff; color: var(--primary); box-shadow: var(--shadow); }
.week { display: grid; grid-template-columns: repeat(6, minmax(150px, 1fr)); gap: 10px; padding: 14px; overflow-x: auto; }
.dh { font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); padding: 4px 4px 10px; }
.period { position: relative; padding: 10px 12px; border-radius: 12px; margin-bottom: 8px; background: color-mix(in srgb, var(--c) 9%, white); border-left: 4px solid var(--c); transition: transform .15s; }
.period:hover { transform: translateY(-2px); }
.time { font-size: 11.5px; font-weight: 650; color: var(--c); } .sub { font-weight: 650; margin: 2px 0; }
.x { position: absolute; top: 6px; right: 6px; border: 0; background: transparent; color: var(--faint); cursor: pointer; opacity: 0; transition: opacity .15s; }
.period:hover .x { opacity: 1; } .x:hover { color: var(--danger); }
.addp { width: 100%; border: 1.5px dashed var(--border); background: transparent; border-radius: 12px; padding: 8px; color: var(--faint); cursor: pointer; }
.addp:hover { border-color: var(--primary); color: var(--primary); }
.none { padding: 6px; } .err { color: var(--danger); }
</style>
