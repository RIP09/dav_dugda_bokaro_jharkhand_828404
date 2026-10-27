<template>
  <span class="badge lock" :title="reason || 'Locked: permanent record'"><Icon name="lock" />{{ label }}</span>
</template>
<script setup>
import Icon from './Icon.vue';
defineProps({ reason: String, label: { type: String, default: 'Locked' } });
</script>
