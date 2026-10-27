<template>
  <Modal :open="!!d" :title="d?.title" :icon="d?.kind === 'confirm' ? (d?.danger ? 'warning' : 'help') : 'edit_note'" :width="480" @close="cancel">
    <template v-if="d">
      <p v-if="d.message" class="msg">{{ d.message }}</p>
      <div v-if="d.kind === 'prompt'" class="field">
        <label v-if="d.label">{{ d.label }} <span v-if="d.required" class="req">*</span></label>
        <textarea v-if="d.multiline" ref="inp" v-model="value" class="input" :placeholder="d.placeholder" />
        <input v-else ref="inp" v-model="value" class="input" :placeholder="d.placeholder" @keydown.enter="ok" />
      </div>
      <div v-if="d.kind === 'form'" class="fields">
        <div v-for="(f, i) in d.fields" :key="f.name" class="field">
          <label>{{ f.label }} <span v-if="f.required" class="req">*</span></label>
          <select v-if="f.type === 'select'" v-model="d.values[f.name]" class="input"><option value="">— Choose —</option><option v-for="o in f.options || []" :key="o.value" :value="o.value">{{ o.label }}</option></select>
          <textarea v-else-if="f.type === 'textarea'" :ref="el => i === 0 && (inp = el)" v-model="d.values[f.name]" class="input" />
          <input v-else :ref="el => i === 0 && (inp = el)" v-model="d.values[f.name]" class="input" :type="{ date: 'date', number: 'number', money: 'number' }[f.type] || 'text'" />
          <span v-if="f.help" class="help">{{ f.help }}</span>
        </div>
      </div>
    </template>
    <template #footer>
      <button class="btn" @click="cancel">{{ d?.cancel || 'Cancel' }}</button>
      <button class="btn" :class="d?.danger ? 'danger solid' : 'primary'" :disabled="!valid" @click="ok">{{ d?.ok || 'OK' }}</button>
    </template>
  </Modal>
</template>
<script setup>
import { computed, ref, watch, nextTick } from 'vue';
import Modal from './Modal.vue';
import { ui, closeDialog } from '../ui/ui';
const d = computed(() => ui.dialog);
const value = ref(''); const inp = ref(null);
watch(d, (v) => { value.value = v?.value || ''; if (v && v.kind !== 'confirm') nextTick(() => inp.value?.focus?.()); });
const valid = computed(() => {
  const x = d.value; if (!x) return false;
  if (x.kind === 'prompt') return !x.required || !!value.value.trim();
  if (x.kind === 'form') return x.fields.every(f => !f.required || String(x.values[f.name] ?? '').trim() !== '');
  return true;
});
function cancel() { closeDialog(d.value?.kind === 'confirm' ? false : null); }
function ok() {
  const x = d.value; if (!x || !valid.value) return;
  if (x.kind === 'prompt') closeDialog(value.value.trim());
  else if (x.kind === 'form') closeDialog(Object.fromEntries(Object.entries(x.values).filter(([, v]) => v !== '')));
  else closeDialog(true);
}
</script>
<style scoped>.msg { margin: 0 0 14px; color: #334155; line-height: 1.5; white-space: pre-line; } .fields { display: grid; gap: 12px; }</style>
