<template>
  <div class="page">
    <PageHead title="Search" sub="Find anyone or anything in the school." icon="search" />
    <div class="card pad"><input v-model="q" class="input big" autofocus placeholder="Name, admission no., phone, receipt no., book title…" /></div>
    <div v-if="loading" class="muted" style="margin:16px">Searching…</div>
    <div v-for="(items, mod) in grouped" :key="mod" class="card fade-up" style="margin-top:14px">
      <div class="card-head"><Icon :name="app.modules[mod]?.icon || 'description'" /><h3>{{ app.modules[mod]?.title || humanize(mod) }}</h3><span class="badge">{{ items.length }}</span></div>
      <div v-for="r in items" :key="r.id" class="res" @click="$router.push({ path: routeFor(r.screen || r.module), query: { id: r.id } })"><b>{{ r.title }}</b><span class="muted small">{{ r.subtitle }}</span></div>
    </div>
    <EmptyState v-if="!loading && q.length > 1 && !results.length" icon="search_off" :title="'Nothing found for “' + q + '”'" />
  </div>
</template>
<script setup>
import { ref, computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import PageHead from '../../components/PageHead.vue';
import Icon from '../../components/Icon.vue';
import EmptyState from '../../components/EmptyState.vue';
import { get } from '../../api/client';
import { app } from '../../state';
import { routeFor } from '../../router';
import { humanize } from '../../ui/format';
const route = useRoute();
const q = ref(route.query.q || ''), results = ref([]), loading = ref(false);
const grouped = computed(() => results.value.reduce((a, r) => { (a[r.module] = a[r.module] || []).push(r); return a; }, {}));
let t;
watch(q, (v) => { clearTimeout(t); if (v.trim().length < 2) { results.value = []; return; } t = setTimeout(async () => { loading.value = true; try { results.value = (await get('search', { q: v.trim() })).data || []; } catch (_) { results.value = []; } loading.value = false; }, 280); }, { immediate: true });
</script>
<style scoped>
.big { height: 48px; font-size: 16px; }
.res { display: flex; flex-direction: column; padding: 11px 18px; border-bottom: 1px solid #f1f5f9; cursor: pointer; } .res:hover { background: var(--panel-2); }
</style>
