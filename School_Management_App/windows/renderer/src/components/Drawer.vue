<template>
  <teleport to="body">
    <transition name="drawer">
      <div v-if="open" class="d-overlay" @mousedown.self="$emit('close')">
        <aside class="drawer" :style="{ width: (width || 620) + 'px' }">
          <slot />
        </aside>
      </div>
    </transition>
  </teleport>
</template>
<script setup>
import { onMounted, onBeforeUnmount } from 'vue';
const props = defineProps({ open: Boolean, width: Number });
const emit = defineEmits(['close']);
const onKey = (e) => { if (e.key === 'Escape' && props.open && !document.querySelector('.overlay')) emit('close'); };
onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>
<style scoped>
.d-overlay { position: fixed; inset: 0; background: rgba(15,23,42,.28); z-index: 400; display: flex; justify-content: flex-end; }
.drawer { height: 100%; max-width: 92vw; background: #fff; box-shadow: var(--shadow-lg); display: flex; flex-direction: column; }
.drawer-enter-active, .drawer-leave-active { transition: background .25s var(--ease); }
.drawer-enter-active .drawer, .drawer-leave-active .drawer { transition: transform .32s var(--ease); }
.drawer-enter-from, .drawer-leave-to { background: transparent; }
.drawer-enter-from .drawer, .drawer-leave-to .drawer { transform: translateX(100%); }
</style>
