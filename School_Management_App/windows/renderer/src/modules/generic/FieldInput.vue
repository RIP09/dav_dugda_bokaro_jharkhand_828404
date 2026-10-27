<template>
  <RefPicker v-if="field.type === 'ref'" :model-value="modelValue" :ref-info="field.ref" :label="labelValue" :invalid="invalid" :disabled="disabled" @update:model-value="set" />
  <select v-else-if="field.type === 'select'" class="input" :class="{ invalid }" :value="modelValue ?? ''" :disabled="disabled" @change="set($event.target.value === '' ? null : $event.target.value)">
    <option value="">— Choose —</option>
    <option v-for="o in field.options || []" :key="o.value" :value="o.value">{{ o.label }}</option>
  </select>
  <div v-else-if="field.type === 'bool'" class="row"><button type="button" class="switch" :class="{ on: truthy }" :disabled="disabled" @click="set(!truthy)" /><span class="small muted">{{ truthy ? 'Yes' : 'No' }}</span></div>
  <textarea v-else-if="field.type === 'textarea'" class="input" :class="{ invalid }" :value="modelValue ?? ''" :disabled="disabled" @input="set($event.target.value)" />
  <div v-else-if="field.type === 'image' || field.type === 'file'" class="upl">
    <img v-if="field.type === 'image' && preview" :src="preview" class="pv" alt="" />
    <div class="grow">
      <div class="row">
        <label class="btn sm" :class="{ disabled }"><Icon :name="uploading ? 'progress_activity' : 'upload'" :class="{ spin: uploading }" />{{ modelValue ? 'Replace' : 'Upload' }}
          <input type="file" hidden :accept="field.type === 'image' ? 'image/*' : undefined" :disabled="disabled || uploading" @change="onFile" /></label>
        <button v-if="modelValue && !disabled" type="button" class="btn sm ghost" @click="set(null); preview = null">Remove</button>
      </div>
      <div v-if="modelValue" class="small muted name">{{ String(modelValue).split('/').pop() }}</div>
    </div>
  </div>
  <input v-else class="input" :class="{ invalid }" :type="htmlType" :step="field.type === 'money' ? '0.01' : (field.type === 'number' ? 'any' : undefined)"
    :value="displayValue" :disabled="disabled" :placeholder="field.placeholder || ''" @input="onInput($event.target.value)" />
</template>
<script setup>
import { computed, ref } from 'vue';
import RefPicker from './RefPicker.vue';
import Icon from '../../components/Icon.vue';
import { upload } from '../../api/client';
import { toastError } from '../../ui/ui';
const props = defineProps({ field: Object, modelValue: null, row: Object, invalid: Boolean, disabled: Boolean, module: String });
const emit = defineEmits(['update:modelValue']);
const uploading = ref(false);
const preview = ref(props.row?.[props.field.name + '_url'] || null);
const labelValue = computed(() => props.row?.[props.field.name + '_label']);
const truthy = computed(() => props.modelValue === true || props.modelValue === 1 || props.modelValue === '1' || props.modelValue === 'true');
const htmlType = computed(() => ({ number: 'number', money: 'number', date: 'date', time: 'time', datetime: 'datetime-local', email: 'email', phone: 'tel' }[props.field.type] || 'text'));
const displayValue = computed(() => {
  const v = props.modelValue; if (v === null || v === undefined) return '';
  if (props.field.type === 'date') return String(v).slice(0, 10);
  if (props.field.type === 'datetime') return String(v).replace(' ', 'T').slice(0, 16);
  if (props.field.type === 'time') return String(v).slice(0, 5);
  return v;
});
function set(v) { emit('update:modelValue', v); }
function onInput(v) {
  if ((props.field.type === 'number' || props.field.type === 'money') && v !== '') return set(Number(v));
  set(v === '' ? null : v);
}
async function onFile(e) {
  const file = e.target.files?.[0]; if (!file) return;
  uploading.value = true;
  try {
    const r = await upload('uploads', file, 'file', { folder: props.module || 'uploads' });
    set(r.data.path); preview.value = r.data.url;
  } catch (err) { toastError(err); }
  uploading.value = false; e.target.value = '';
}
</script>
<style scoped>
.upl { display: flex; gap: 12px; align-items: center; }
.pv { width: 64px; height: 64px; border-radius: 14px; object-fit: cover; background: #f1f5f9; }
.name { margin-top: 4px; word-break: break-all; }
label.btn.disabled { opacity: .5; pointer-events: none; }
</style>
