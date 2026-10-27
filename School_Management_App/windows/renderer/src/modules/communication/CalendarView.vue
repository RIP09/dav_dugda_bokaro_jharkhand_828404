<template>
  <div class="page">
    <PageHead title="Academic calendar" sub="Events, exams and holidays." icon="event" color="#ec4899">
      <button class="btn icon" @click="shift(-1)"><Icon name="chevron_left" /></button>
      <b class="mlabel">{{ label }}</b>
      <button class="btn icon" @click="shift(1)"><Icon name="chevron_right" /></button>
      <button class="btn" @click="cur = new Date(); cur.setDate(1)">Today</button>
      <button v-if="eventsMeta?.permissions?.create" class="btn primary" @click="add()"><Icon name="add" />Add event</button>
    </PageHead>
    <div class="card cal">
      <div v-for="d in ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']" :key="d" class="wd">{{ d }}</div>
      <div v-for="c in cells" :key="c.key" class="cell" :class="{ out: !c.inMonth, today: c.today, sun: c.sun }" @dblclick="eventsMeta?.permissions?.create && add(c.iso)">
        <div class="dn">{{ c.d }}</div>
        <div v-for="e in c.events" :key="e.id + c.key" class="ev" :class="e.type" :title="e.title" @click="openEv(e)">{{ e.title }}</div>
      </div>
    </div>
    <div class="legend small muted"><span class="ev holiday">Holiday</span><span class="ev exam">Exam</span><span class="ev meeting">Meeting</span><span class="ev">Event</span> · Double-click a day to add an event.</div>
    <RecordForm v-if="eventsMeta" :open="formOpen" :meta="eventsMeta" :row="editing" :defaults="defaults" @close="formOpen = false" @saved="load" />
    <Modal :open="!!shown" :title="shown?.title" icon="event" :width="460" @close="shown = null">
      <p class="muted">{{ date(shown?.start_date) }}<span v-if="shown?.end_date && shown.end_date !== shown.start_date"> – {{ date(shown.end_date) }}</span><span v-if="shown?.location"> · {{ shown.location }}</span></p>
      <p style="white-space:pre-wrap">{{ shown?.description }}</p>
      <template v-if="eventsMeta?.permissions?.update" #footer><button class="btn" @click="edit(shown)"><Icon name="edit" />Edit</button></template>
    </Modal>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue';
import PageHead from '../../components/PageHead.vue';
import Icon from '../../components/Icon.vue';
import Modal from '../../components/Modal.vue';
import RecordForm from '../generic/RecordForm.vue';
import { get } from '../../api/client';
import { app } from '../../state';
import { date, today } from '../../ui/format';
const cur = ref((() => { const d = new Date(); d.setDate(1); return d; })());
const events = ref([]), formOpen = ref(false), editing = ref(null), defaults = ref({}), shown = ref(null);
const eventsMeta = computed(() => app.modules.events);
const label = computed(() => cur.value.toLocaleDateString('en-IN', { month: 'long', year: 'numeric' }));
const iso = (d) => new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
const cells = computed(() => {
  const y = cur.value.getFullYear(), m = cur.value.getMonth(); const first = new Date(y, m, 1); const start = new Date(first); start.setDate(1 - ((first.getDay() + 6) % 7));
  const t = today(); const out = [];
  for (let i = 0; i < 42; i++) {
    const d = new Date(start); d.setDate(start.getDate() + i); const s = iso(d);
    out.push({ key: s, iso: s, d: d.getDate(), inMonth: d.getMonth() === m, today: s === t, sun: d.getDay() === 0,
      events: events.value.filter(e => { const a = String(e.start_date).slice(0, 10), b = String(e.end_date || e.start_date).slice(0, 10); return s >= a && s <= b; }) });
  }
  return out;
});
async function load() {
  const all = [];
  try { all.push(...((await get('events')).data || [])); } catch (_) {}
  try { const h = await get('records/holidays', { per_page: 200 }); all.push(...(h.data || []).map(x => ({ ...x, id: 'h' + x.id, title: x.title || x.name, start_date: x.date || x.start_date, end_date: x.end_date, type: 'holiday' }))); } catch (_) {}
  try { const ex = await get('exams'); all.push(...(ex.data || []).map(x => ({ id: 'x' + x.id, title: x.name, start_date: x.start_date, end_date: x.end_date, type: 'exam' }))); } catch (_) {}
  events.value = all;
}
onMounted(load);
function shift(n) { const d = new Date(cur.value); d.setMonth(d.getMonth() + n); cur.value = d; }
function add(day) { editing.value = null; defaults.value = day ? { start_date: day } : {}; formOpen.value = true; }
function openEv(e) { shown.value = e; }
async function edit(e) { shown.value = null; if (typeof e.id !== 'number') return; try { editing.value = (await get('records/events/' + e.id)).data; formOpen.value = true; } catch (_) {} }
</script>
<style scoped>
.mlabel { min-width: 150px; text-align: center; font-size: 15px; }
.cal { display: grid; grid-template-columns: repeat(7, 1fr); overflow: hidden; }
.wd { padding: 10px; font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; background: var(--panel-2); border-bottom: 1px solid var(--border); }
.cell { min-height: 108px; border-right: 1px solid #eef0f5; border-bottom: 1px solid #eef0f5; padding: 6px; transition: background .12s; }
.cell:hover { background: #fafbff; } .cell.out { background: #fbfbfd; color: var(--faint); } .cell.sun .dn { color: var(--danger); }
.dn { font-size: 12.5px; font-weight: 650; margin-bottom: 4px; width: 24px; height: 24px; display: grid; place-items: center; border-radius: 50%; }
.cell.today .dn { background: var(--primary); color: #fff; }
.ev { font-size: 11.5px; padding: 3px 7px; border-radius: 6px; margin-bottom: 3px; background: var(--primary-50); color: var(--primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; font-weight: 600; display: inline-block; max-width: 100%; }
.cell .ev { display: block; }
.ev.holiday, .ev.vacation { background: var(--danger-50); color: var(--danger); } .ev.exam { background: #f3e8ff; color: #7e22ce; } .ev.meeting { background: var(--warning-50); color: var(--warning); }
.legend { display: flex; gap: 8px; align-items: center; margin-top: 10px; }
</style>
