<template>
  <div class="csp">
    <select class="input" :value="classId ?? ''" @change="onClass($event.target.value)">
      <option value="">{{ allowAll ? 'All classes' : 'Choose class' }}</option>
      <option v-for="c in classes" :key="c.id" :value="c.id">{{ className(c) }}</option>
    </select>
    <select v-if="!classOnly" class="input" :value="modelValue ?? ''" :disabled="!classId" @change="emit('update:modelValue', $event.target.value ? Number($event.target.value) : null)">
      <option value="">{{ !classId ? 'Section (choose class first)' : (allowAll ? 'All sections of ' + className(currentClass) : 'Choose section') }}</option>
      <option v-for="s in sectionsOf" :key="s.id" :value="s.id">{{ sectionLabel(s) }}{{ mineIds.has(s.id) ? '  ★ my class' : '' }}</option>
    </select>
  </div>
</template>
<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { loadSections, sectionLabel, classDisplayName } from './sections';
import { session } from '../api/client';
const props = defineProps({ modelValue: [Number, null], classOnly: Boolean, allowAll: Boolean, initialClass: [Number, null] });
const emit = defineEmits(['update:modelValue', 'update:classId', 'section']);
const all = ref([]); const classId = ref(props.initialClass || null);
const classes = computed(() => { const m = new Map(); for (const s of all.value) { const c = s.school_class || { id: s.class_id, name: s.class_name }; if (c && !m.has(c.id)) m.set(c.id, { ...c, order: c.numeric_order ?? c.id }); } return [...m.values()].sort((a, b) => a.order - b.order); });
const className = (c) => classDisplayName(c?.name);
const currentClass = computed(() => classes.value.find(c => c.id === classId.value));
const sectionsOf = computed(() => all.value.filter(s => s.class_id === classId.value));
onMounted(async () => {
  all.value = await loadSections();
  if (props.modelValue) { const s = all.value.find(x => x.id === props.modelValue); if (s) { classId.value = s.class_id; return; } }
  // Teachers start on their own class (class teacher sections).
  const uid = session.user?.id;
  if (!props.classOnly && session.user?.role === 'teacher') {
    const mine = all.value.find(x => x.teacher_id === uid);
    if (mine) { classId.value = mine.class_id; emit('update:classId', mine.class_id); emit('update:modelValue', mine.id); }
  }
});
const mineIds = computed(() => new Set(all.value.filter(x => x.teacher_id && x.teacher_id === session.user?.id).map(x => x.id)));
watch(() => props.modelValue, (v) => { const s = all.value.find(x => x.id === v); if (s) { classId.value = s.class_id; emit('section', s); } });
function onClass(v) {
  classId.value = v ? Number(v) : null; emit('update:classId', classId.value);
  const list = sectionsOf.value;
  emit('update:modelValue', list.length === 1 ? list[0].id : null);
}
</script>
<style scoped>.csp { display: flex; gap: 8px; } .csp select { width: auto; min-width: 150px; }</style>
