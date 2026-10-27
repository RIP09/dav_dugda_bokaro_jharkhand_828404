<template><span :class="outlined ? 'material-icons-outlined' : 'material-icons'" :style="size ? { fontSize: size + 'px' } : null" aria-hidden="true">{{ glyph }}</span></template>
<script setup>
import { computed } from 'vue';
const props = defineProps({ name: String, size: [Number, String], outlined: Boolean });
// Map a few non-Material names the server may send to Material Icons ligatures.
const alias = { students: 'school', staff: 'badge', parents: 'family_restroom', attendance: 'fact_check', fees: 'payments', due: 'pending_actions',
  teacher: 'co_present', money: 'currency_rupee', rupee: 'currency_rupee', book: 'menu_book', bus: 'directions_bus', exam: 'quiz', notice: 'campaign',
  user: 'person', users: 'groups', calendar: 'event', leave: 'event_busy', library: 'local_library', transport: 'directions_bus', homework: 'assignment' };
const glyph = computed(() => { const n = (props.name || 'circle').trim(); return alias[n] || n.replace(/-/g, '_'); });
</script>
