<template>
  <div class="empty fade-up">
    <div class="ico"><Icon :name="icon" :size="30" /></div>
    <h3>{{ title }}</h3>
    <p v-if="text" class="muted">{{ text }}</p>
    <slot />
  </div>
</template>
<script setup>
import Icon from './Icon.vue';
defineProps({ icon: { type: String, default: 'inbox' }, title: String, text: String });
</script>
<style scoped>
.empty { text-align: center; padding: 48px 20px; }
.ico { width: 64px; height: 64px; border-radius: 20px; margin: 0 auto 14px; display: grid; place-items: center; background: var(--primary-50); color: var(--primary); }
p { max-width: 420px; margin: 6px auto 14px; }
</style>
