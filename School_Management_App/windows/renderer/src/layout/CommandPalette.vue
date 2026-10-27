<template>
  <Modal :open="ui.paletteOpen" title="Search" icon="search" :width="640" @close="close">
    <input ref="inp" v-model="q" class="input big" placeholder="Type a name, admission no., receipt no., book, or a menu item…" @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" @keydown.enter.prevent="choose(items[sel])" />
    <div class="results">
      <div v-if="loading" class="muted pad">Searching…</div>
      <div v-for="(it, i) in items" :key="it.k" class="res" :class="{ sel: i === sel }" @mouseenter="sel = i" @click="choose(it)">
        <span class="ri"><Icon :name="it.icon" :size="18" /></span>
        <div class="grow"><div class="rt">{{ it.title }}</div><div v-if="it.subtitle" class="rs">{{ it.subtitle }}</div></div>
        <span class="badge">{{ it.kind }}</span>
      </div>
      <div v-if="!loading && q.length > 1 && !items.length" class="muted pad">Nothing found for “{{ q }}”.</div>
      <div v-if="!q" class="muted pad small">Tip: press <kbd>Ctrl K</kbd> from anywhere to search.</div>
    </div>
  </Modal>
</template>
<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import Modal from '../components/Modal.vue';
import Icon from '../components/Icon.vue';
import { ui } from '../ui/ui';
import { app } from '../state';
import { get } from '../api/client';
import { routeFor } from '../router';
import { humanize } from '../ui/format';

const router = useRouter();
const q = ref(''), sel = ref(0), loading = ref(false), remote = ref([]), inp = ref(null);
let t, seq = 0;
watch(() => ui.paletteOpen, (o) => { if (o) { q.value = ''; remote.value = []; nextTick(() => inp.value?.focus()); } });
watch(q, (v) => {
  clearTimeout(t); sel.value = 0;
  if (v.trim().length < 2) { remote.value = []; loading.value = false; return; }
  loading.value = true;
  t = setTimeout(async () => {
    const my = ++seq;
    try { const r = await get('search', { q: v.trim() }); if (my === seq) remote.value = r.data || []; } catch (_) { if (my === seq) remote.value = []; }
    if (my === seq) loading.value = false;
  }, 250);
});
const items = computed(() => {
  const s = q.value.trim().toLowerCase();
  const menu = s ? (app.menu || []).flatMap(g => g.items.map(i => ({ ...i, group: g.group }))).filter(i => i.title.toLowerCase().includes(s)).slice(0, 5)
    .map(i => ({ k: 'm' + i.key + i.screen, title: i.title, subtitle: i.group, icon: i.icon || 'arrow_forward', kind: 'Menu', go: { path: routeFor(i.screen) } })) : [];
  const rec = remote.value.map((r, i) => ({ k: 'r' + i, title: r.title, subtitle: r.subtitle, icon: app.modules[r.module]?.icon || 'description', kind: app.modules[r.module]?.title || humanize(r.module),
    go: { path: routeFor(r.screen || r.module), query: r.id ? { id: r.id } : {} } }));
  return [...rec, ...menu];
});
function move(d) { const n = items.value.length; if (n) sel.value = (sel.value + d + n) % n; }
function choose(it) { if (!it) return; close(); router.push(it.go); }
function close() { ui.paletteOpen = false; }
</script>
<style scoped>
.big { height: 46px; font-size: 15px; }
.results { margin-top: 10px; max-height: 52vh; overflow: auto; }
.res { display: flex; align-items: center; gap: 12px; padding: 9px 10px; border-radius: 10px; cursor: pointer; }
.res.sel { background: var(--primary-50); }
.ri { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; background: #f1f5f9; color: var(--primary); }
.rt { font-weight: 600; } .rs { font-size: 12.5px; color: var(--muted); }
.pad { padding: 14px 6px; }
kbd { font: 11px ui-monospace, monospace; background: #fff; border: 1px solid var(--border); border-radius: 6px; padding: 2px 6px; }
</style>
