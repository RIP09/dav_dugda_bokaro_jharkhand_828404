<template>
  <div>
    <div v-if="loading" class="muted p">Loading history…</div>
    <div v-else-if="error" class="muted p"><Icon name="lock" /> {{ error }}</div>
    <div v-else-if="!rows.length" class="muted p">No changes recorded yet.</div>
    <ol v-else class="tl">
      <li v-for="h in rows" :key="h.id" class="fade-up">
        <span class="dot" :class="h.action"><Icon :name="actionIcon(h.action)" :size="14" /></span>
        <div class="body">
          <div class="head"><b>{{ h.user_name || 'System' }}</b> <span class="badge" :class="actionColor(h.action)">{{ actionLabel(h.action) }}</span>
            <span v-if="showRecord" class="muted">{{ h.module_title || humanize(h.module) }} · {{ h.record_title || ('#' + h.record_id) }}</span>
            <span class="grow" /><span class="faint small" :title="datetime(h.created_at)">{{ datetime(h.created_at) }}</span></div>
          <table v-if="changeRows(h).length" class="chg">
            <tr v-for="c in changeRows(h)" :key="c.field"><td class="f">{{ humanize(c.field) }}</td><td class="old">{{ show(c.old) }}</td><td class="arrow">→</td><td class="new">{{ show(c.new) }}</td></tr>
          </table>
          <div v-if="h.reason || h.note" class="small muted">Reason: {{ h.reason || h.note }}</div>
          <div v-if="h.ip" class="faint small">IP {{ h.ip }}</div>
        </div>
      </li>
    </ol>
  </div>
</template>
<script setup>
import Icon from '../../components/Icon.vue';
import { datetime, humanize } from '../../ui/format';
defineProps({ rows: { type: Array, default: () => [] }, loading: Boolean, error: String, showRecord: Boolean });
const icons = { created: 'add', create: 'add', updated: 'edit', update: 'edit', deleted: 'delete', delete: 'delete', locked: 'lock', cancelled: 'block', cancel: 'block', login: 'login', action: 'bolt' };
const actionIcon = (a) => icons[a] || 'bolt';
const actionLabel = (a) => humanize(a || 'change');
const actionColor = (a) => (/creat/.test(a) ? 'green' : /delet|cancel/.test(a) ? 'red' : /lock/.test(a) ? 'lock' : /updat/.test(a) ? 'indigo' : 'amber');
function changeRows(h) {
  const c = h.changes; if (!c || typeof c !== 'object') return [];
  return Object.entries(c).map(([field, v]) => (v && typeof v === 'object' && ('old' in v || 'new' in v)) ? { field, old: v.old, new: v.new } : { field, old: undefined, new: v });
}
const show = (v) => (v === null || v === undefined || v === '' ? '—' : typeof v === 'object' ? JSON.stringify(v) : String(v));
</script>
<style scoped>
.p { padding: 16px 4px; }
.tl { list-style: none; margin: 0; padding: 0 0 0 6px; position: relative; }
.tl::before { content: ''; position: absolute; left: 17px; top: 6px; bottom: 6px; width: 2px; background: var(--border); }
.tl li { display: flex; gap: 12px; padding: 8px 0; position: relative; }
.dot { width: 24px; height: 24px; border-radius: 50%; display: grid; place-items: center; background: #e0e7ff; color: var(--primary); flex: none; z-index: 1; border: 3px solid #fff; box-sizing: content-box; }
.dot.created, .dot.create { background: var(--success-50); color: var(--success); } .dot.deleted, .dot.cancelled, .dot.cancel { background: var(--danger-50); color: var(--danger); }
.body { flex: 1; min-width: 0; background: var(--panel-2); border: 1px solid var(--border); border-radius: 12px; padding: 10px 12px; }
.head { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 13.5px; }
.chg { width: 100%; margin-top: 8px; border-collapse: collapse; font-size: 12.5px; }
.chg td { padding: 4px 6px; border-top: 1px dashed #e2e8f0; vertical-align: top; word-break: break-word; }
.chg .f { color: var(--muted); width: 30%; } .chg .old { color: #be123c; text-decoration: line-through; text-decoration-color: rgba(190,18,60,.4); } .chg .new { color: #047857; font-weight: 550; } .chg .arrow { color: var(--faint); width: 16px; }
</style>
