<template>
  <div class="off fade-up">
    <div class="ill"><Icon name="cloud_off" :size="46" /></div>
    <h1>Can't reach the school server</h1>
    <p class="muted">{{ message }}<br />Check that this PC is connected to the internet. If the problem continues, check the server address.</p>
    <div class="row" style="justify-content:center">
      <button class="btn primary" @click="$emit('retry')"><Icon name="refresh" />Retry</button>
      <router-link class="btn" to="/setup"><Icon name="dns" />Server settings</router-link>
    </div>
    <p class="small faint">Server: {{ session.server }}</p>
  </div>
</template>
<script setup>
import Icon from '../components/Icon.vue';
import { session } from '../api/client';
defineProps({ message: String });
</script>
<style scoped>
.off { max-width: 520px; margin: 9vh auto; text-align: center; padding: 20px; }
.ill { width: 96px; height: 96px; border-radius: 30px; margin: 0 auto 20px; display: grid; place-items: center; background: linear-gradient(135deg, #e0e7ff, #fae8ff); color: var(--primary); animation: float 3s ease-in-out infinite; }
@keyframes float { 50% { transform: translateY(-6px); } }
p { line-height: 1.6; margin: 10px 0 20px; }
</style>
