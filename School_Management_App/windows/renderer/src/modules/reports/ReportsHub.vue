<template>
  <div class="page">
    <PageHead title="Reports" sub="Printable reports and Excel exports for the office and inspections." icon="insights" color="#0ea5e9" />
    <div class="grid tiles stagger">
      <div v-for="t in tiles" :key="t.title" class="card tile" @click="t.run()">
        <span class="ti" :style="{ background: t.color }"><Icon :name="t.icon" /></span>
        <div><b>{{ t.title }}</b><div class="small muted">{{ t.text }}</div></div>
      </div>
    </div>
    <div class="card pad" style="margin-top:18px">
      <h3>Export any section</h3>
      <p class="small muted">Choose a section to download as Excel. (Every list also has its own Export button with filters.)</p>
      <div class="row"><select v-model="mod" class="input" style="width:280px"><option v-for="m in exportable" :key="m.key" :value="m.key">{{ m.group }} · {{ m.title }}</option></select>
        <button class="btn" :disabled="!mod" @click="exp('xlsx')"><Icon name="table_view" />Excel</button><button class="btn" :disabled="!mod" @click="exp('csv')">CSV</button><button class="btn" :disabled="!mod" @click="exp('pdf')"><Icon name="picture_as_pdf" />PDF</button></div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import PageHead from '../../components/PageHead.vue';
import Icon from '../../components/Icon.vue';
import { app } from '../../state';
import { downloadFile } from '../../ui/files';
import { routeFor } from '../../router';
const router = useRouter();
const has = (s) => (app.menu || []).some(g => g.items.some(i => i.screen === s)) || !!app.modules[s];
const all = [
  { s: 'attendance_report', title: 'Attendance register', text: 'Month-wise per class, PDF / Excel', icon: 'calendar_month', color: '#10b981' },
  { s: 'report_cards', title: 'Report cards & marksheets', text: 'Per student or whole class', icon: 'grading', color: '#ec4899' },
  { s: 'fee_reminders', title: 'Fee dues list', text: 'Parents with pending fees + WhatsApp links', icon: 'pending_actions', color: '#f59e0b' },
  { s: 'payments', title: 'Fee collection register', text: 'All receipts, filter by date and export', icon: 'receipt_long', color: '#6366f1' },
  { s: 'transactions', title: 'Income & expenses', text: 'Cash book, export to Excel', icon: 'account_balance', color: '#0ea5e9' },
  { s: 'payslips', title: 'Salary register', text: 'Salary slips by month', icon: 'request_quote', color: '#a855f7' },
  { s: 'book_issues', title: 'Library issue register', text: 'Issued, returned, overdue and fines', icon: 'local_library', color: '#ef4444' },
  { s: 'audit', title: 'Audit log', text: 'Every change, who and when', icon: 'history', color: '#64748b' },
];
const tiles = computed(() => all.filter(t => has(t.s)).map(t => ({ ...t, run: () => router.push(routeFor(t.s)) })));
const exportable = computed(() => app.moduleList.filter(m => m.permissions?.export));
const mod = ref(null);
const exp = (format) => downloadFile('export/' + mod.value, { format }, { filename: `${app.modules[mod.value]?.title || mod.value}.${format}` });
</script>
<style scoped>
.tiles { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }
.tile { display: flex; gap: 14px; align-items: center; padding: 18px; cursor: pointer; transition: all .2s var(--ease); }
.tile:hover { transform: translateY(-3px); box-shadow: 0 14px 30px -12px rgba(15,23,42,.2); }
.ti { width: 46px; height: 46px; border-radius: 14px; display: grid; place-items: center; color: #fff; flex: none; }
</style>
