<template>
  <div class="page">
    <div v-if="!meta" class="card"><EmptyState icon="block" title="Not available" :text="app.loaded ? 'This section is not available for your login, or the server does not offer it yet.' : 'Loading…'" /></div>
    <template v-else>
      <div class="page-head">
        <div class="hico"><Icon :name="meta.icon || 'folder'" /></div>
        <div><h1>{{ meta.title }}</h1><div class="sub">{{ total }} record{{ total === 1 ? '' : 's' }}<span v-if="meta.group"> · {{ meta.group }}</span></div></div>
        <span class="grow" />
        <slot name="extra-actions" />
        <button v-for="b in listButtons" :key="b.label" class="btn" @click="b.run()"><Icon :name="b.icon" />{{ b.label }}</button>
        <div v-if="can(meta, 'export')" class="pop-wrap" v-click-away="() => (exportOpen = false)">
          <button class="btn" @click="exportOpen = !exportOpen"><Icon name="download" />Export<Icon name="expand_more" :size="16" /></button>
          <transition name="pop"><div v-if="exportOpen" class="pop" @click="exportOpen = false">
            <a class="mi" @click="exportAs('xlsx')"><Icon name="table_view" />Excel (.xlsx)</a>
            <a class="mi" @click="exportAs('csv')"><Icon name="description" />CSV</a>
            <a class="mi" @click="exportAs('pdf')"><Icon name="picture_as_pdf" />PDF</a>
          </div></transition>
        </div>
        <router-link v-if="can(meta, 'import')" class="btn" :to="{ path: '/s/import', query: { module: meta.key } }"><Icon name="upload_file" />Import</router-link>
        <button v-if="can(meta, 'create')" class="btn primary" @click="openForm(null)"><Icon name="add" />Add new</button>
      </div>
      <div v-if="meta.lockable && meta.lock_note" class="infobar" style="margin-bottom:14px"><Icon name="lock" /><div>{{ meta.lock_note }}</div></div>
      <div class="card">
        <div class="toolbar">
          <div class="search"><Icon name="search" :size="18" /><input v-model="q" :placeholder="'Search ' + meta.title.toLowerCase() + '…'" /></div>
          <template v-for="f in filters" :key="f.name">
            <RefPicker v-if="f.type === 'ref'" v-model="filter[f.name]" :ref-info="f.ref" :placeholder="fieldLabel(f)" :params="filterParams(f)" class="fl" sm />
            <select v-else-if="f.type === 'select' || f.type === 'bool'" v-model="filter[f.name]" class="input sm fl">
              <option :value="undefined">{{ fieldLabel(f) }}: all</option>
              <template v-if="f.type === 'bool'"><option value="1">Yes</option><option value="0">No</option></template>
              <option v-for="o in f.options || []" v-else :key="o.value" :value="o.value">{{ o.label }}</option>
            </select>
            <input v-else-if="f.type === 'date'" v-model="filter[f.name]" type="date" class="input sm fl" :title="fieldLabel(f)" />
            <input v-else v-model.lazy="filter[f.name]" class="input sm fl" :placeholder="fieldLabel(f)" />
          </template>
          <button v-if="hasFilters" class="btn sm ghost" @click="clearFilters"><Icon name="filter_alt_off" />Clear</button>
          <span class="grow" />
          <button class="btn sm ghost icon" title="Reload" @click="load"><Icon name="refresh" :class="{ spin: loading }" /></button>
        </div>
        <div class="table-wrap">
          <table class="data">
            <thead><tr>
              <th v-for="f in columns" :key="f.name" class="sortable" @click="sortBy(f.name)">{{ fieldLabel(f) }}
                <Icon v-if="sortField === f.name" :name="sortDesc ? 'arrow_downward' : 'arrow_upward'" :size="14" /></th>
              <th style="width:110px" />
            </tr></thead>
            <tbody v-if="loading && !rows.length"><tr v-for="i in 8" :key="i"><td :colspan="columns.length + 1"><div class="skeleton" style="height:18px" /></td></tr></tbody>
            <transition-group v-else tag="tbody" name="rowfade">
              <tr v-for="r in rows" :key="r.id" class="click" :class="{ locked: r._locked }" @click="openRecord(r)">
                <td v-for="(f, i) in columns" :key="f.name"><b v-if="i === 0 && f.type !== 'image'" class="first">{{ displayFirst(f, r) }}</b><FieldValue v-else :field="f" :row="r" /></td>
                <td class="right nowrap" @click.stop>
                  <LockBadge v-if="r._locked" :reason="r._lock_reason" />
                  <template v-else>
                    <button v-if="meta.permissions?.update && r._can_edit !== false" class="btn sm ghost icon" title="Edit" @click="openForm(r)"><Icon name="edit" :size="17" /></button>
                    <button v-if="meta.permissions?.delete && r._can_delete !== false" class="btn sm ghost icon" title="Delete" @click="remove(r)"><Icon name="delete_outline" :size="17" /></button>
                  </template>
                </td>
              </tr>
            </transition-group>
          </table>
          <EmptyState v-if="!loading && !rows.length" :icon="meta.icon || 'inbox'" :title="q || hasFilters ? 'Nothing matches' : 'No records yet'" :text="q || hasFilters ? 'Try a different search or clear the filters.' : (can(meta, 'create') ? 'Click “Add new” to create the first one' + (can(meta, 'import') ? ', or import from Excel.' : '.') : '')" />
          <div v-if="error" class="err-row"><Icon name="error" /> {{ error }} <button class="btn sm" @click="load">Retry</button></div>
        </div>
        <div v-if="lastPage > 1 || total > 10" class="pager">
          <span class="muted small">Showing {{ (page - 1) * perPage + 1 }}–{{ Math.min(page * perPage, total) }} of {{ total }}</span>
          <span class="grow" />
          <select v-model.number="perPage" class="input sm" style="width:auto"><option :value="25">25 / page</option><option :value="50">50 / page</option><option :value="100">100 / page</option></select>
          <button class="btn sm icon" :disabled="page <= 1" @click="page--"><Icon name="chevron_left" /></button>
          <span class="small">Page {{ page }} / {{ lastPage }}</span>
          <button class="btn sm icon" :disabled="page >= lastPage" @click="page++"><Icon name="chevron_right" /></button>
        </div>
      </div>
    </template>
    <RecordForm :open="formOpen" :meta="meta" :row="editing" :defaults="defaults" @close="formOpen = false" @saved="onSaved" />
    <RecordDrawer ref="drawer" :open="drawerOpen" :meta="meta" :id="openId" :initial="openRow" @close="closeRecord" @edit="openForm" @changed="load" />
  </div>
</template>
<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Icon from '../../components/Icon.vue';
import EmptyState from '../../components/EmptyState.vue';
import LockBadge from '../../components/LockBadge.vue';
import FieldValue from './FieldValue.vue';
import RefPicker from './RefPicker.vue';
import RecordForm from './RecordForm.vue';
import RecordDrawer from './RecordDrawer.vue';
import { listFields, filterFields, fieldLabel, can, rowTitle, dependencyOf } from './meta';
import { app } from '../../state';
import { get, del } from '../../api/client';
import { confirmBox, toast, toastError } from '../../ui/ui';
import { downloadFile } from '../../ui/files';
import { extensions } from '../registry';

const props = defineProps({ moduleKey: String, presetFilter: Object });
const route = useRoute(); const router = useRouter();
const key = computed(() => props.moduleKey || route.params.module);
const meta = computed(() => app.modules[key.value]);
const columns = computed(() => listFields(meta.value).slice(0, 7));
const filters = computed(() => filterFields(meta.value).slice(0, 5));
const listButtons = computed(() => extensions[key.value]?.listButtons?.({ router }) || []);
const rows = ref([]), total = ref(0), lastPage = ref(1), page = ref(1), perPage = ref(25), loading = ref(false), error = ref('');
const q = ref(''); const filter = reactive({ ...(props.presetFilter || {}) });
const sortField = ref(''), sortDesc = ref(true);
const formOpen = ref(false), editing = ref(null), drawerOpen = ref(false), openId = ref(null), openRow = ref(null), exportOpen = ref(false), drawer = ref(null);
const defaults = computed(() => ({ ...Object.fromEntries(Object.entries(filter).filter(([, v]) => v !== undefined && v !== '' && v !== null)) }));
const hasFilters = computed(() => Object.values(filter).some(v => v !== undefined && v !== null && v !== ''));
const vClickAway = { mounted(el, b) { el._ca = (e) => { if (!el.contains(e.target)) b.value(); }; document.addEventListener('mousedown', el._ca); }, unmounted(el) { document.removeEventListener('mousedown', el._ca); } };

const query = () => ({ q: q.value || undefined, page: page.value, per_page: perPage.value, sort: sortField.value ? (sortDesc.value ? '-' : '') + sortField.value : undefined,
  filter: Object.fromEntries(Object.entries(filter).filter(([, v]) => v !== undefined && v !== null && v !== '')) });
let seq = 0;
async function load() {
  if (!meta.value) return;
  const my = ++seq; loading.value = true; error.value = '';
  try {
    const r = await get('records/' + key.value, query());
    if (my !== seq) return;
    rows.value = r.data || []; total.value = r.meta?.total ?? rows.value.length; lastPage.value = r.meta?.last_page ?? 1;
  } catch (e) { if (my === seq) { error.value = e.message; rows.value = []; } }
  if (my === seq) loading.value = false;
}
let t;
watch(q, () => { clearTimeout(t); t = setTimeout(() => { page.value = 1; load(); }, 300); });
watch(filter, () => { page.value = 1; load(); }, { deep: true });
watch([page, perPage], load);
watch(meta, (m, old) => { if (m && !old) load(); });
watch(() => route.query.id, (id) => { if (id) { openId.value = id; openRow.value = null; drawerOpen.value = true; } });
onMounted(() => { load(); if (route.query.id) { openId.value = route.query.id; drawerOpen.value = true; } });

function sortBy(f) { if (sortField.value === f) sortDesc.value = !sortDesc.value; else { sortField.value = f; sortDesc.value = false; } load(); }
// A Section filter lists only the chosen Class's sections (each still labelled "Class 6 – A").
function filterParams(f) {
  const d = dependencyOf(f, meta.value?.fields || []);
  const v = d ? filter[d.field] : null;
  return d && v ? { ['filter[' + d.key + ']']: v, per_page: 200 } : undefined;
}
watch(() => (meta.value?.fields || []).map(f => { const d = dependencyOf(f, meta.value.fields); return d ? filter[d.field] : undefined; }), (now, before) => {
  if (!before || now.length !== before.length) return;
  (meta.value?.fields || []).forEach((f, i) => { if (now[i] !== before[i] && before[i] !== undefined && filter[f.name]) filter[f.name] = undefined; });
});
function clearFilters() { Object.keys(filter).forEach(k => (filter[k] = undefined)); }
function displayFirst(f, r) { const v = f.type === 'ref' ? r[f.name + '_label'] : r[f.name]; return v ?? rowTitle(meta.value, r); }
function openRecord(r) { openId.value = r.id; openRow.value = r; drawerOpen.value = true; }
function closeRecord() { drawerOpen.value = false; if (route.query.id) router.replace({ query: { ...route.query, id: undefined } }); }
function openForm(r) {
  if (r && r._locked) { toast(r._lock_reason || 'This record is locked.', 'locked'); return; }
  editing.value = r || null; formOpen.value = true;
}
function onSaved(saved) { load(); if (drawerOpen.value) drawer.value?.reload(); else if (saved?.id && !editing.value) openRecord(saved); }
async function remove(r) {
  if (!(await confirmBox({ title: 'Delete this record?', message: `“${rowTitle(meta.value, r)}” will be deleted. This is recorded in the history.`, ok: 'Delete', danger: true }))) return;
  try { await del(`records/${key.value}/${r.id}`); toast('Deleted'); load(); } catch (e) { toastError(e); load(); }
}
function exportAs(format) { const { page: _p, per_page: _pp, ...qq } = query(); downloadFile('export/' + key.value, { ...qq, format }, { filename: `${meta.value.title}.${format}` }); }
</script>
<style scoped>
.hico { width: 46px; height: 46px; border-radius: 14px; display: grid; place-items: center; background: linear-gradient(135deg, #eef2ff, #f5f3ff); color: var(--primary); }
.toolbar { display: flex; gap: 8px; align-items: center; padding: 12px 14px; border-bottom: 1px solid var(--border); flex-wrap: wrap; }
.search { display: flex; align-items: center; gap: 6px; height: 32px; padding: 0 10px; border: 1px solid var(--border); border-radius: 9px; width: 280px; color: var(--faint); background: #fff; }
.search:focus-within { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,.12); }
.search input { border: 0; outline: 0; font: inherit; flex: 1; color: var(--text); font-size: 13px; }
.fl { width: 170px; }
.first { font-weight: 600; }
.nowrap { white-space: nowrap; }
.pager { display: flex; align-items: center; gap: 8px; padding: 10px 14px; border-top: 1px solid var(--border); }
.err-row { padding: 14px; color: var(--danger); display: flex; gap: 8px; align-items: center; }
.pop-wrap { position: relative; }
.pop { position: absolute; right: 0; top: calc(100% + 6px); background: #fff; border: 1px solid var(--border); border-radius: 12px; box-shadow: var(--shadow-lg); padding: 6px; width: 190px; z-index: 60; }
.mi { display: flex; gap: 10px; align-items: center; padding: 8px 10px; border-radius: 8px; color: var(--text); cursor: pointer; }
.mi:hover { background: var(--panel-2); }
.rowfade-enter-active { transition: all .3s var(--ease); } .rowfade-enter-from { opacity: 0; transform: translateY(4px); }
</style>
