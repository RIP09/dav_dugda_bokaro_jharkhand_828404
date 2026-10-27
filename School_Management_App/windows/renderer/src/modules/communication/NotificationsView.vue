<template>
  <div class="page">
    <PageHead title="Notifications" :sub="unread + ' unread'" icon="notifications" color="#ef4444"><button class="btn" @click="readAll"><Icon name="done_all" />Mark all read</button></PageHead>
    <div class="card">
      <div v-if="loading" style="padding:16px"><div v-for="i in 6" :key="i" class="skeleton" style="height:56px;margin-bottom:8px" /></div>
      <div v-for="n in rows" :key="n.id" class="n" :class="{ unread: !n.read_at }" @click="open(n)">
        <span class="ic"><Icon :name="app.modules[n.module]?.icon || 'notifications'" /></span>
        <div class="grow"><b>{{ n.title }}</b><div class="muted">{{ n.body }}</div></div>
        <span class="faint small">{{ ago(n.created_at) }}</span>
      </div>
      <EmptyState v-if="!loading && !rows.length" icon="notifications_none" title="You're all caught up" />
      <div v-if="lastPage > page" class="center" style="padding:12px"><button class="btn" @click="more">Load more</button></div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import PageHead from '../../components/PageHead.vue';
import Icon from '../../components/Icon.vue';
import EmptyState from '../../components/EmptyState.vue';
import { get, post } from '../../api/client';
import { app } from '../../state';
import { routeFor } from '../../router';
import { ago } from '../../ui/format';
const router = useRouter();
const rows = ref([]), loading = ref(true), unread = ref(0), page = ref(1), lastPage = ref(1);
async function load(p = 1) { const r = await get('notifications', { page: p, per_page: 30 }).catch(() => ({ data: [] })); rows.value = p === 1 ? (r.data || []) : [...rows.value, ...(r.data || [])]; unread.value = r.meta?.unread ?? 0; app.unread = unread.value; lastPage.value = r.meta?.last_page || 1; page.value = p; loading.value = false; }
onMounted(() => load());
const more = () => load(page.value + 1);
async function open(n) { if (!n.read_at) { await post(`notifications/${n.id}/read`).catch(() => {}); n.read_at = new Date().toISOString(); unread.value = Math.max(0, unread.value - 1); app.unread = unread.value; } if (n.module) router.push({ path: routeFor(n.module), query: n.record_id ? { id: n.record_id } : {} }); }
async function readAll() { await post('notifications/read-all').catch(() => {}); rows.value.forEach(n => (n.read_at = n.read_at || 'x')); unread.value = 0; app.unread = 0; }
</script>
<style scoped>
.n { display: flex; gap: 14px; align-items: center; padding: 14px 18px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background .12s; }
.n:hover { background: var(--panel-2); } .n.unread { background: #fafaff; } .n.unread b::before { content: '● '; color: var(--primary); }
.ic { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: var(--primary-50); color: var(--primary); }
</style>
