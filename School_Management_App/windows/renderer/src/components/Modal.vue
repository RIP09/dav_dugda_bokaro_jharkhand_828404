<template>
  <teleport to="body">
    <transition name="modal">
      <div v-if="open" class="overlay" @mousedown.self="!persistent && $emit('close')">
        <div class="modal" :style="{ width: (width || 560) + 'px' }" role="dialog">
          <div class="mhead"><Icon v-if="icon" :name="icon" class="micon" /><h2>{{ title }}</h2><span class="grow" /><button class="btn ghost icon" @click="$emit('close')"><Icon name="close" /></button></div>
          <div class="mbody"><slot /></div>
          <div v-if="$slots.footer" class="mfoot"><slot name="footer" /></div>
        </div>
      </div>
    </transition>
  </teleport>
</template>
<script setup>
import { onMounted, onBeforeUnmount } from 'vue';
import Icon from './Icon.vue';
const props = defineProps({ open: Boolean, title: String, icon: String, width: Number, persistent: Boolean });
const emit = defineEmits(['close']);
const onKey = (e) => { if (e.key === 'Escape' && props.open) emit('close'); };
onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>
<style scoped>
.overlay { position: fixed; inset: 0; background: rgba(15,23,42,.45); backdrop-filter: blur(3px); display: grid; place-items: center; z-index: 500; padding: 24px; }
.modal { background: #fff; border-radius: 18px; box-shadow: var(--shadow-lg); max-height: calc(100vh - 48px); display: flex; flex-direction: column; max-width: 100%; }
.mhead { display: flex; align-items: center; gap: 10px; padding: 16px 18px 12px 22px; }
.micon { color: var(--primary); }
.mbody { padding: 6px 22px 18px; overflow: auto; }
.mfoot { padding: 14px 22px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 10px; background: var(--panel-2); border-radius: 0 0 18px 18px; }
.modal-enter-active, .modal-leave-active { transition: opacity .2s var(--ease); }
.modal-enter-active .modal, .modal-leave-active .modal { transition: transform .25s var(--ease); }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .modal { transform: translateY(14px) scale(.98); } .modal-leave-to .modal { transform: scale(.98); }
</style>
