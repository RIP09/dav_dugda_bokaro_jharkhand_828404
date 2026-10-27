<template><span class="material-icons spin" :style="{ fontSize: (size || 20) + 'px', color: 'var(--primary)' }">progress_activity</span></template>
<script setup>defineProps({ size: Number });</script>
