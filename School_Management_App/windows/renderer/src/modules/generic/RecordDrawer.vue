<template>
  <Drawer :open="open" :width="660" @close="$emit('close')">
    <div class="dh">
      <div class="avatar" :class="{ locked: row?._locked }"><img v-if="photo" :src="photo" alt="" /><Icon v-else :name="row?._locked ? 'lock' : (meta?.icon || 'description')" /></div>
      <div class="grow" style="min-width:0">
        <div class="small muted">{{ meta?.title }}</div>
        <h2 class="t">{{ title }}</h2>
        <div v-if="subtitle" class="muted small">{{ subtitle }}</div>
      </div>
      <button class="btn ghost icon" title="Close" @click="$emit('close')"><Icon name="close" /></button>
    </div>
    <div v-if="row?._locked" class="lockbar mx"><Icon name="lock" /><div><b>Locked — permanent record.</b> {{ row._lock_reason || meta?.lock_note || 'This record cannot be edited or deleted.' }}</div></div>
    <div class="acts mx">
      <button v-if="canEdit" class="btn primary sm" @click="$emit('edit', row)"><Icon name="edit" />Edit</button>
      <button v-else-if="meta?.permissions?.update" class="btn sm" disabled :title="row?._lock_reason || 'Locked'"><Icon name="lock" />Edit</button>
      <button v-for="a in visibleActions" :key="a.key" class="btn sm" :class="{ danger: /cancel|reject|delete/.test(a.key) }" :disabled="busy" @click="run(a)"><Icon :name="actionIcon(a)" />{{ a.label }}</button>
      <button v-for="b in extButtons" :key="b.label" class="btn sm" @click="b.run()"><Icon :name="b.icon || 'open_in_new'" />{{ b.label }}</button>
      <span class="grow" />
      <button v-if="canDelete" class="btn danger sm" @click="remove"><Icon name="delete" />Delete</button>
    </div>
    <div class="tabs">
      <div class="tab" :class="{ active: tab === 'details' }" @click="tab = 'details'"><Icon name="article" :size="17" />Details</div>
      <div v-if="showHistory" class="tab" :class="{ active: tab === 'history' }" @click="openHistory"><Icon name="history" :size="17" />History</div>
      <div v-for="t in extTabs" :key="t.key" class="tab" :class="{ active: tab === t.key }" @click="tab = t.key"><Icon :name="t.icon || 'tab'" :size="17" />{{ t.label }}</div>
    </div>
    <div class="dbody">
      <div v-if="loading" class="skeleton" style="height:220px" />
      <template v-else-if="tab === 'details' && row">
        <dl class="kv">
          <template v-for="f in fields" :key="f.name">
            <dt>{{ fieldLabel(f) }} <Icon v-if="f.immutable" name="lock" :size="13" class="faint" title="Fixed once created" /></dt>
            <dd><FieldValue :field="f" :row="row" big /></dd>
          </template>
          <dt>Created</dt><dd class="muted">{{ datetime(row.created_at) }}</dd>
          <dt>Last changed</dt><dd class="muted">{{ datetime(row.updated_at) }}</dd>
        </dl>
      </template>
      <HistoryList v-else-if="tab === 'history'" :rows="history" :loading="hLoading" :error="hError" />
      <component :is="tabComp" v-else-if="tabComp" :row="row" :meta="meta" />
    </div>
  </Drawer>
</template>
<script setup>
import { ref, computed, watch, defineAsyncComponent } from 'vue';
import Drawer from '../../components/Drawer.vue';
import Icon from '../../components/Icon.vue';
import FieldValue from './FieldValue.vue';
import HistoryList from './HistoryList.vue';
import { detailFields, fieldLabel, rowTitle, rowSubtitle } from './meta';
import { evaluateWhen } from './when';
import { runAction } from './actions';
import { get, del, session } from '../../api/client';
import { confirmBox, toast, toastError } from '../../ui/ui';
import { datetime } from '../../ui/format';
import { extensions } from '../registry';

const props = defineProps({ open: Boolean, meta: Object, id: [Number, String], initial: Object });
const emit = defineEmits(['close', 'edit', 'changed']);
const row = ref(null), loading = ref(false), tab = ref('details'), busy = ref(false);
const history = ref([]), hLoading = ref(false), hError = ref('');
const ext = computed(() => extensions[props.meta?.key] || {});
const fields = computed(() => detailFields(props.meta));
const title = computed(() => rowTitle(props.meta, row.value));
const subtitle = computed(() => rowSubtitle(props.meta, row.value));
const photo = computed(() => { const f = (props.meta?.fields || []).find(x => x.type === 'image'); return f ? row.value?.[f.name + '_url'] : null; });
const canEdit = computed(() => row.value && props.meta?.permissions?.update && !row.value._locked && row.value._can_edit !== false);
const canDelete = computed(() => row.value && props.meta?.permissions?.delete && !row.value._locked && row.value._can_delete !== false);
const showHistory = computed(() => session.user?.role === 'admin' || props.meta?.permissions?.history);
const visibleActions = computed(() => (props.meta?.actions || []).filter(a => row.value && evaluateWhen(a.when, row.value) && (a.kind === 'pdf' || a.kind === 'print' || !row.value._locked || /cancel|pdf|print|reverse|unlock/.test(a.key) || a.allow_locked)));
const extButtons = computed(() => (row.value && ext.value.buttons ? ext.value.buttons(row.value) : []));
const extTabs = computed(() => (ext.value.tabs || []).filter(t => !t.roles || t.roles.includes(session.user?.role)));
const tabCache = {};
const tabComp = computed(() => { const t = extTabs.value.find(x => x.key === tab.value); if (!t) return null; return tabCache[t.key] || (tabCache[t.key] = defineAsyncComponent(t.component)); });
const actionIcon = (a) => ({ pdf: 'picture_as_pdf', print: 'print', cancel: 'block', convert: 'swap_horiz', issue: 'verified', pay: 'payments', lock_results: 'lock', publish: 'publish', approve: 'check_circle', reject: 'cancel', return: 'assignment_return' }[a.key] || (a.kind === 'pdf' ? 'picture_as_pdf' : 'bolt'));

async function load() {
  if (!props.meta || props.id == null) return;
  loading.value = !props.initial; if (props.initial) row.value = props.initial;
  try { row.value = (await get(`records/${props.meta.key}/${props.id}`)).data; ext.value.onOpen?.(row.value); }
  catch (e) { toastError(e); if (!row.value) emit('close'); }
  loading.value = false;
}
watch(() => [props.open, props.id], () => { if (props.open) { tab.value = 'details'; history.value = []; hError.value = ''; row.value = props.initial || null; load(); } }, { immediate: true });

async function openHistory() {
  tab.value = 'history'; if (history.value.length) return;
  hLoading.value = true; hError.value = '';
  try { history.value = (await get(`records/${props.meta.key}/${props.id}/history`)).data || []; }
  catch (e) { hError.value = e.status === 403 ? 'Only the principal/admin can see the change history.' : e.message; }
  hLoading.value = false;
}
async function run(a) {
  busy.value = true;
  const r = await runAction(props.meta, row.value, a);
  busy.value = false;
  if (r && a.kind !== 'pdf') { if (r.data && r.data.id === row.value.id) row.value = r.data; else await load(); history.value = []; emit('changed'); }
}
async function remove() {
  const ok = await confirmBox({ title: 'Delete this record?', message: `“${title.value}” will be deleted. This is recorded in the history.`, ok: 'Delete', danger: true });
  if (!ok) return;
  try { await del(`records/${props.meta.key}/${row.value.id}`); toast('Deleted'); emit('changed'); emit('close'); }
  catch (e) { toastError(e); if (e.status === 423) load(); }
}
defineExpose({ reload: load });
</script>
<style scoped>
.dh { display: flex; gap: 14px; align-items: center; padding: 20px 20px 14px; }
.avatar { width: 52px; height: 52px; border-radius: 16px; background: var(--primary-50); color: var(--primary); display: grid; place-items: center; overflow: hidden; flex: none; }
.avatar img { width: 100%; height: 100%; object-fit: cover; }
.avatar.locked { background: var(--lock-50); color: var(--lock); }
.t { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.mx { margin: 0 20px 12px; }
.acts { display: flex; gap: 8px; flex-wrap: wrap; }
.dbody { flex: 1; overflow: auto; padding: 18px 20px 30px; }
</style>
