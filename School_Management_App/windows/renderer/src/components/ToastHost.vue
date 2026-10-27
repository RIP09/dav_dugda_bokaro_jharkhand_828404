<template>
  <div class="toasts no-print">
    <transition-group name="toast">
      <div v-for="t in ui.toasts" :key="t.id" class="toast" :class="t.type">
        <Icon :name="icons[t.type] || 'info'" /><span>{{ t.message }}</span>
      </div>
    </transition-group>
  </div>
</template>
<script setup>
import { ui } from '../ui/ui';
import Icon from './Icon.vue';
const icons = { success: 'check_circle', error: 'error', info: 'info', locked: 'lock', warning: 'warning' };
</script>
<style scoped>
.toasts { position: fixed; right: 22px; bottom: 22px; display: flex; flex-direction: column; gap: 10px; z-index: 1000; max-width: 440px; }
.toast { display: flex; gap: 10px; align-items: flex-start; padding: 12px 16px; border-radius: 12px; background: #0f172a; color: #fff; box-shadow: var(--shadow-lg); font-size: 13.5px; line-height: 1.4; }
.toast.success .material-icons { color: #34d399; } .toast.error .material-icons { color: #fb7185; } .toast.locked .material-icons { color: #fbbf24; } .toast.info .material-icons { color: #93c5fd; }
.toast-enter-active, .toast-leave-active { transition: all .3s var(--ease); }
.toast-enter-from { opacity: 0; transform: translateX(30px); } .toast-leave-to { opacity: 0; transform: translateY(10px); }
</style>
