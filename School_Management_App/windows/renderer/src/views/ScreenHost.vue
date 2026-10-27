<template>
  <component :is="comp" v-if="comp" :key="$route.params.screen" />
  <div v-else class="page"><EmptyState icon="construction" title="Screen not found" :text="'This screen (' + $route.params.screen + ') is not part of this version.'" /></div>
</template>
<script setup>
import { computed, defineAsyncComponent } from 'vue';
import { useRoute } from 'vue-router';
import { screens } from '../modules/registry';
import EmptyState from '../components/EmptyState.vue';
const route = useRoute();
const cache = {};
const comp = computed(() => {
  const id = route.params.screen; const loader = screens[id];
  if (!loader) return null;
  return cache[id] || (cache[id] = defineAsyncComponent(loader));
});
</script>
