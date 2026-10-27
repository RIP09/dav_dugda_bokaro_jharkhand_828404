<template>
  <div class="page-head">
    <div class="hico" :style="color ? { background: color + '1a', color } : null"><Icon :name="icon" /></div>
    <div><h1>{{ title }}</h1><div v-if="sub" class="sub">{{ sub }}</div></div>
    <span class="grow" />
    <slot />
  </div>
</template>
<script setup>
import Icon from './Icon.vue';
defineProps({ title: String, sub: String, icon: { type: String, default: 'apps' }, color: String });
</script>
<style scoped>.hico { width: 46px; height: 46px; border-radius: 14px; display: grid; place-items: center; background: linear-gradient(135deg, #eef2ff, #f5f3ff); color: var(--primary); }</style>
