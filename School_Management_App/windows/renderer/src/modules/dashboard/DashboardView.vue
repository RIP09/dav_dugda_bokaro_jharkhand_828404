<template>
  <div class="page">
    <div class="hero fade-up">
      <div class="hero-txt">
        <div class="hello">{{ greeting }}, {{ firstName }} 👋</div>
        <h1>{{ app.school?.name || 'DAV Public School, Dugda' }}</h1>
        <div class="meta"><span><Icon name="place" :size="16" />{{ app.school?.address }}</span><span v-if="app.school?.current_session"><Icon name="event_note" :size="16" />Session {{ app.school.current_session }}</span><span><Icon name="today" :size="16" />{{ todayText }}</span></div>
      </div>
      <div class="principal">
        <div class="pav">{{ initials(principal) }}</div>
        <div><div class="small pl">Principal</div><b>{{ principal }}</b></div>
      </div>
    </div>

    <div v-if="loading" class="grid cards"><div v-for="i in 4" :key="i" class="skeleton" style="height:88px" /></div>
    <div v-else class="grid cards stagger">
      <StatCard v-for="(c, i) in cards" :key="c.label" :label="c.label" :value="c.value" :icon="c.icon" :color="c.color || colors[i % colors.length]" :hint="c.hint" />
    </div>

    <div v-if="quick.length" class="quick fade-up">
      <router-link v-for="q in quick" :key="q.screen" :to="routeFor(q.screen)" class="qa"><span class="qi" :style="{ background: q.color }"><Icon :name="q.icon" /></span>{{ q.title }}</router-link>
    </div>

    <div v-if="charts.length" class="grid chartgrid">
      <div v-for="c in charts" :key="c.id" class="card pad fade-up" :class="{ wide: (c.labels || []).length > 8 && c.type !== 'pie' && c.type !== 'doughnut' }">
        <h3 style="margin-bottom:12px">{{ c.title }}</h3>
        <ChartBox :chart="c" />
      </div>
    </div>

    <div class="grid two">
      <div v-if="notices.length" class="card fade-up">
        <div class="card-head"><Icon name="campaign" /><h3>Latest notices</h3><span class="grow" /><router-link v-if="has('notices')" to="/m/notices" class="small">All notices</router-link></div>
        <div v-for="n in notices" :key="n.id" class="li"><div class="lt">{{ n.title }}</div><div class="small muted">{{ date(n.publish_date || n.created_at) }} · {{ n.audience || 'All' }}</div></div>
      </div>
      <div v-if="events.length" class="card fade-up">
        <div class="card-head"><Icon name="event" /><h3>Coming up</h3><span class="grow" /><router-link v-if="hasScreen('calendar')" to="/s/calendar" class="small">Calendar</router-link></div>
        <div v-for="e in events" :key="e.id" class="li ev"><div class="dbox"><b>{{ day(e.start_date) }}</b><span>{{ mon(e.start_date) }}</span></div><div><div class="lt">{{ e.title }}</div><div class="small muted">{{ e.location || e.type }}</div></div></div>
      </div>
      <div v-if="alerts.length" class="card fade-up">
        <div class="card-head"><Icon name="notifications_active" /><h3>Needs attention</h3></div>
        <div v-for="(a, i) in alerts" :key="i" class="li"><Icon name="error_outline" :size="18" style="color:var(--warning)" /> {{ a.text || a.label || a }}</div>
      </div>
    </div>
    <p v-if="error" class="muted small">{{ error }}</p>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue';
import Icon from '../../components/Icon.vue';
import StatCard from '../../components/StatCard.vue';
import ChartBox from '../../components/ChartBox.vue';
import { app, PRINCIPAL_DEFAULT } from '../../state';
import { session, get } from '../../api/client';
import { routeFor } from '../../router';
import { date } from '../../ui/format';

const loading = ref(true), cards = ref([]), charts = ref([]), notices = ref([]), events = ref([]), alerts = ref([]), error = ref('');
const colors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#a855f7', '#ec4899', '#0ea5e9'];
const principal = computed(() => app.school?.principal_name || PRINCIPAL_DEFAULT);
const firstName = computed(() => (session.user?.name || '').split(' ')[0] || 'there');
const greeting = computed(() => { const h = new Date().getHours(); return h < 12 ? 'Good morning' : h < 17 ? 'Good afternoon' : 'Good evening'; });
const todayText = new Date().toLocaleDateString('en-IN', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
const initials = (n) => (n || '').split(/\s+/).map(s => s[0]).slice(0, 2).join('');
const allItems = computed(() => (app.menu || []).flatMap(g => g.items));
const hasScreen = (s) => allItems.value.some(i => i.screen === s);
const has = (k) => !!app.modules[k];
const QUICK = [
  { screen: 'attendance_take', title: 'Take attendance', icon: 'fact_check', color: '#10b981' },
  { screen: 'fee_collect', title: 'Collect fees', icon: 'point_of_sale', color: '#6366f1' },
  { screen: 'admissions', title: 'New admission', icon: 'person_add', color: '#f59e0b' },
  { screen: 'marks_entry', title: 'Enter marks', icon: 'edit_note', color: '#a855f7' },
  { screen: 'certificates', title: 'Issue certificate', icon: 'workspace_premium', color: '#ec4899' },
  { screen: 'notices', title: 'Post notice', icon: 'campaign', color: '#0ea5e9' },
  { screen: 'visitors', title: 'Visitor entry', icon: 'badge', color: '#14b8a6' },
  { screen: 'book_issues', title: 'Issue book', icon: 'local_library', color: '#ef4444' },
  { screen: 'import', title: 'Import from Excel', icon: 'upload_file', color: '#64748b' },
];
const quick = computed(() => QUICK.filter(q => hasScreen(q.screen) || has(q.screen)).slice(0, 7));
const day = (d) => new Date(d).getDate();
const mon = (d) => new Date(d).toLocaleString('en-IN', { month: 'short' });

onMounted(async () => {
  try {
    const r = await get('dashboard/charts');
    const d = r.data || r;
    cards.value = d.cards || []; charts.value = d.charts || [];
  } catch (e) { error.value = ''; }
  try {
    const p1 = (await get('dashboard')).data || {};
    if (!cards.value.length) cards.value = (p1.stats || []).map(s => ({ label: s.label, value: s.value, icon: s.icon }));
    notices.value = (p1.notices || []).slice(0, 5); events.value = (p1.events || p1.upcoming_events || []).slice(0, 5);
    alerts.value = p1.alerts || [];
  } catch (_) {}
  if (!events.value.length) { try { events.value = ((await get('events')).data || []).filter(e => e.start_date >= new Date().toISOString().slice(0, 10)).slice(0, 5); } catch (_) {} }
  if (!notices.value.length) { try { notices.value = ((await get('notices')).data || []).slice(0, 5); } catch (_) {} }
  loading.value = false;
});
</script>
<style scoped>
.hero { display: flex; align-items: center; gap: 20px; padding: 24px 26px; border-radius: 20px; color: #fff; margin-bottom: 18px; position: relative; overflow: hidden;
  background: radial-gradient(1200px 300px at 0% 0%, #6366f1 0%, transparent 60%), radial-gradient(800px 300px at 100% 100%, #db2777 0%, transparent 55%), linear-gradient(135deg, #1e1b4b, #312e81); }
.hero::after { content: ''; position: absolute; inset: 0; background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40'%3E%3Ccircle cx='2' cy='2' r='1' fill='white' opacity='.12'/%3E%3C/svg%3E"); pointer-events: none; }
.hero-txt { flex: 1; position: relative; z-index: 1; }
.hello { opacity: .85; font-size: 14px; margin-bottom: 4px; } .hero h1 { font-size: 26px; color: #fff; }
.meta { display: flex; gap: 18px; flex-wrap: wrap; margin-top: 10px; font-size: 13px; opacity: .85; } .meta span { display: flex; gap: 5px; align-items: center; }
.principal { display: flex; gap: 12px; align-items: center; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.18); padding: 12px 16px; border-radius: 16px; backdrop-filter: blur(6px); position: relative; z-index: 1; }
.pav { width: 44px; height: 44px; border-radius: 50%; display: grid; place-items: center; background: linear-gradient(135deg, #fbbf24, #f97316); font-weight: 800; }
.pl { opacity: .75; }
.cards { grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); margin-bottom: 18px; }
.quick { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
.qa { display: flex; align-items: center; gap: 10px; padding: 10px 16px 10px 10px; background: #fff; border: 1px solid var(--border); border-radius: 14px; color: var(--text); font-weight: 600; font-size: 13.5px; transition: all .2s var(--ease); box-shadow: var(--shadow); }
.qa:hover { transform: translateY(-2px); border-color: #c7d2fe; }
.qi { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; color: #fff; }
.chartgrid { grid-template-columns: repeat(auto-fill, minmax(420px, 1fr)); margin-bottom: 18px; }
.chartgrid .wide { grid-column: 1 / -1; }
.two { grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); }
.li { padding: 11px 18px; border-bottom: 1px solid #f1f5f9; display: flex; gap: 10px; align-items: center; } .li:last-child { border: 0; }
.li:not(.ev) { display: block; } .lt { font-weight: 600; font-size: 13.5px; }
.dbox { width: 46px; height: 46px; border-radius: 12px; background: var(--primary-50); color: var(--primary); display: flex; flex-direction: column; align-items: center; justify-content: center; line-height: 1.1; flex: none; }
.dbox b { font-size: 16px; } .dbox span { font-size: 11px; text-transform: uppercase; }
</style>
