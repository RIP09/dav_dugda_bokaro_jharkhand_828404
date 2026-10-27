<template>
  <aside class="sidebar no-print">
    <div class="brand" @click="$router.push('/dashboard')">
      <img v-if="app.school?.logo_url" :src="app.school.logo_url" class="logo" alt="" />
      <div v-else class="logo mark">DAV</div>
      <div v-if="!collapsed" class="bname"><b>{{ app.school?.short_name || 'DAV Dugda' }}</b><span>School Management</span></div>
    </div>
    <div v-if="!collapsed" class="filter"><Icon name="filter_list" :size="16" /><input v-model="q" placeholder="Find a menu item" /></div>
    <nav class="nav">
      <template v-for="g in groups" :key="g.group">
        <div class="group" :class="{ open: isOpen(g) }">
          <button v-if="g.items.length > 1 || collapsed" class="ghead" :title="g.group" @click="toggleGroup(g.group)">
            <Icon :name="groupIcon(g)" /><span v-if="!collapsed" class="gl">{{ g.group }}</span>
            <Icon v-if="!collapsed" name="expand_more" class="chev" :size="18" />
          </button>
          <router-link v-else class="item single" :to="g.items[0].to" :title="g.items[0].title">
            <Icon :name="g.items[0].icon || groupIcon(g)" /><span>{{ g.items[0].title }}</span>
          </router-link>
          <div v-if="g.items.length > 1 || collapsed" class="items" :style="{ maxHeight: isOpen(g) ? (g.items.length * 40 + 8) + 'px' : '0px' }">
            <router-link v-for="it in g.items" :key="it.key + it.to" class="item" :to="it.to" :title="it.title">
              <Icon :name="it.icon || 'chevron_right'" :size="18" /><span>{{ it.title }}</span>
            </router-link>
          </div>
        </div>
      </template>
    </nav>
    <div class="foot">
      <button class="btn ghost collapse" :title="collapsed ? 'Expand menu' : 'Collapse menu'" @click="$emit('toggle')"><Icon :name="collapsed ? 'chevron_right' : 'chevron_left'" /><span v-if="!collapsed">Collapse</span></button>
    </div>
  </aside>
</template>
<script setup>
import { ref, computed, reactive, watch } from 'vue';
import { useRoute } from 'vue-router';
import Icon from '../components/Icon.vue';
import { app } from '../state';
import { routeFor, menuRoutes } from '../router';
import { groupIcons } from '../modules/registry';
const props = defineProps({ collapsed: Boolean });
defineEmits(['toggle']);
const route = useRoute();
const q = ref('');
const open = reactive(JSON.parse(localStorage.getItem('dav.groups') || '{}'));
const groups = computed(() => {
  const s = q.value.trim().toLowerCase();
  return (app.menu || []).map(g => ({ ...g, items: menuRoutes(g.items || []).filter(i => !s || i.title.toLowerCase().includes(s) || g.group.toLowerCase().includes(s)) })).filter(g => g.items.length);
});
const groupIcon = (g) => groupIcons[g.group] || g.icon || g.items?.[0]?.icon || 'folder';
const containsActive = (g) => g.items.some(i => route.path === i.to);
const isOpen = (g) => !!q.value || (open[g.group] ?? containsActive(g));
function toggleGroup(name) { const g = groups.value.find(x => x.group === name); open[name] = !isOpen(g); localStorage.setItem('dav.groups', JSON.stringify(open)); }
watch(() => route.path, () => { for (const g of groups.value) if (containsActive(g) && open[g.group] === false) open[g.group] = true; });
</script>
<style scoped>
.sidebar { width: 262px; flex: none; background: linear-gradient(180deg, var(--side-1), var(--side-2)); color: var(--side-text); display: flex; flex-direction: column; transition: width .28s var(--ease); }
.collapsed .sidebar, :global(.collapsed) .sidebar { width: 70px; }
.brand { display: flex; align-items: center; gap: 12px; padding: 18px 16px 14px; cursor: pointer; }
.logo { width: 40px; height: 40px; border-radius: 12px; object-fit: cover; flex: none; background: #fff; }
.logo.mark { display: grid; place-items: center; font-weight: 800; font-size: 13px; color: #fff; background: linear-gradient(135deg, #f59e0b, #ef4444); letter-spacing: .03em; box-shadow: 0 6px 18px -6px rgba(245,158,11,.7); }
.bname { display: flex; flex-direction: column; line-height: 1.2; white-space: nowrap; overflow: hidden; }
.bname b { color: #fff; font-size: 15px; } .bname span { font-size: 12px; opacity: .7; }
.filter { margin: 2px 14px 8px; display: flex; align-items: center; gap: 6px; background: rgba(255,255,255,.07); border-radius: 9px; padding: 0 10px; }
.filter input { background: transparent; border: 0; outline: 0; color: #fff; height: 32px; font: inherit; font-size: 13px; width: 100%; }
.filter input::placeholder { color: rgba(199,210,254,.55); }
.nav { flex: 1; overflow-y: auto; overflow-x: hidden; padding: 4px 10px 10px; }
.nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); }
.ghead, .item { display: flex; align-items: center; gap: 11px; width: 100%; padding: 9px 10px; border-radius: 10px; color: var(--side-text); background: none; border: 0; font: inherit; font-size: 13.5px; cursor: pointer; text-align: left; white-space: nowrap; transition: background .15s, color .15s; }
.ghead:hover, .item:hover { background: rgba(255,255,255,.07); color: #fff; }
.gl { flex: 1; font-weight: 550; }
.chev { transition: transform .25s var(--ease); opacity: .6; }
.group.open .chev { transform: rotate(180deg); }
.items { overflow: hidden; transition: max-height .3s var(--ease); }
.items .item { padding: 8px 10px 8px 22px; font-size: 13px; color: rgba(199,210,254,.85); }
:global(.collapsed) .items .item { padding-left: 12px; }
.item.router-link-active { background: rgba(129,140,248,.22); color: #fff; box-shadow: inset 3px 0 0 #818cf8; }
.item span { overflow: hidden; text-overflow: ellipsis; }
:global(.collapsed) .item span, :global(.collapsed) .filter { display: none; }
.foot { padding: 10px; border-top: 1px solid rgba(255,255,255,.07); }
.collapse { color: var(--side-text); width: 100%; justify-content: flex-start; }
.collapse:hover { background: rgba(255,255,255,.07) !important; color: #fff; }
</style>
