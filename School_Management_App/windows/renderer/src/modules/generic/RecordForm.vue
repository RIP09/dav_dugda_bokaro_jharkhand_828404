<template>
  <Modal :open="open" :title="(row ? 'Edit ' : 'Add ') + singular" :icon="row ? 'edit' : 'add_circle'" :width="wide ? 820 : 620" persistent @close="$emit('close')">
    <div v-if="row?._locked" class="lockbar" style="margin-bottom:14px"><Icon name="lock" /><div><b>This record is locked.</b> {{ row._lock_reason || meta.lock_note || 'It is a permanent record and cannot be changed.' }}</div></div>
    <div v-if="generalError" class="err-box"><Icon name="error" />{{ generalError }}</div>
    <form class="form-grid" @submit.prevent="save">
      <div v-for="f in fields" :key="f.name" class="field" :class="{ span2: wideField(f) }">
        <label>{{ fieldLabel(f) }} <span v-if="f.required && !(row && f.immutable)" class="req">*</span>
          <span v-if="row && f.immutable" class="badge lock small-badge" title="Can be set only when the record is created"><Icon name="lock" />Fixed</span></label>
        <FieldInput v-model="form[f.name]" :field="f" :row="row || {}" :module="meta.key" :invalid="!!errors[f.name]" :disabled="saving || !!row?._locked || (row && f.immutable)" />
        <span v-if="errors[f.name]" class="err">{{ errors[f.name] }}</span>
        <span v-else-if="f.help" class="help">{{ f.help }}</span>
      </div>
    </form>
    <template #footer>
      <span v-if="row" class="small muted grow">Every change is saved in the record's history.</span>
      <button class="btn" @click="$emit('close')">Cancel</button>
      <button class="btn primary" :disabled="saving || !!row?._locked" @click="save"><Icon :name="saving ? 'progress_activity' : 'check'" :class="{ spin: saving }" />{{ row ? 'Save changes' : 'Add' }}</button>
    </template>
  </Modal>
</template>
<script setup>
import { ref, reactive, computed, watch } from 'vue';
import Modal from '../../components/Modal.vue';
import Icon from '../../components/Icon.vue';
import FieldInput from './FieldInput.vue';
import { formFields, fieldLabel } from './meta';
import { post, put } from '../../api/client';
import { toast } from '../../ui/ui';

const props = defineProps({ open: Boolean, meta: Object, row: Object, defaults: Object });
const emit = defineEmits(['close', 'saved']);
const form = reactive({}); const errors = reactive({}); const saving = ref(false); const generalError = ref('');
const fields = computed(() => formFields(props.meta));
const wide = computed(() => fields.value.length > 8);
const singular = computed(() => (props.meta?.singular || props.meta?.title || 'record').replace(/ies$/, 'y').replace(/s$/, '').toLowerCase());
const wideField = (f) => f.type === 'textarea' || f.type === 'image' || f.type === 'file';
let original = {};

watch(() => [props.open, props.row], () => {
  if (!props.open) return;
  Object.keys(form).forEach(k => delete form[k]); Object.keys(errors).forEach(k => delete errors[k]); generalError.value = '';
  for (const f of fields.value) {
    let v = props.row ? props.row[f.name] : (props.defaults?.[f.name] ?? f.default ?? (f.type === 'bool' ? false : null));
    if (f.type === 'date' && v) v = String(v).slice(0, 10);
    form[f.name] = v;
  }
  original = JSON.parse(JSON.stringify(form));
}, { immediate: true });

async function save() {
  Object.keys(errors).forEach(k => delete errors[k]); generalError.value = '';
  for (const f of fields.value) {
    if (f.required && !(props.row && f.immutable) && f.type !== 'bool' && (form[f.name] === null || form[f.name] === undefined || form[f.name] === '')) errors[f.name] = fieldLabel(f) + ' is required.';
  }
  if (Object.keys(errors).length) return;
  const payload = {};
  for (const f of fields.value) {
    if (props.row && f.immutable) continue;
    if (props.row && JSON.stringify(form[f.name]) === JSON.stringify(original[f.name])) continue;
    payload[f.name] = form[f.name];
  }
  if (props.row && !Object.keys(payload).length) { emit('close'); toast('Nothing changed', 'info'); return; }
  saving.value = true;
  try {
    const r = props.row ? await put(`records/${props.meta.key}/${props.row.id}`, payload) : await post(`records/${props.meta.key}`, payload);
    toast(r?.message || (props.row ? 'Changes saved' : 'Added successfully'));
    emit('saved', r?.data); emit('close');
  } catch (e) {
    if (e.errors) for (const [k, v] of Object.entries(e.errors)) errors[k.split('.')[0]] = Array.isArray(v) ? v[0] : v;
    generalError.value = e.status === 423 ? ('Locked: ' + e.message) : (e.errors && Object.keys(e.errors).every(k => fields.value.some(f => f.name === k.split('.')[0])) ? '' : e.message);
  } finally { saving.value = false; }
}
</script>
<style scoped>
.err-box { display: flex; gap: 8px; align-items: center; margin-bottom: 14px; padding: 10px 12px; border-radius: 10px; background: var(--danger-50); color: #9f1239; font-size: 13px; }
.small-badge { font-size: 10.5px; padding: 0 6px; margin-left: 4px; }
</style>
