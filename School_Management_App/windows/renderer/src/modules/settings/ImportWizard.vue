<template>
  <div class="page">
    <PageHead title="Import from Excel" sub="Move your paper / Excel registers into the system in 4 easy steps." icon="upload_file" color="#10b981" />
    <div class="steps"><div v-for="(s, i) in STEPS" :key="s" class="step" :class="{ on: step === i, done: step > i }"><span>{{ step > i ? '✓' : i + 1 }}</span>{{ s }}</div></div>
    <div class="card pad fade-up" :key="step">
      <template v-if="step === 0">
        <h3>What do you want to import?</h3>
        <div class="mods">
          <button v-for="m in MODULES" :key="m.k" class="mod" :class="{ on: mod === m.k }" @click="mod = m.k"><Icon :name="m.i" :size="26" /><b>{{ m.l }}</b><span class="small muted">{{ m.d }}</span></button>
        </div>
      </template>
      <template v-else-if="step === 1">
        <h3>Download the template</h3>
        <p class="muted">Open it in Excel, keep the first row (column names) as it is, and fill one row per {{ current.one }}. Dates as YYYY-MM-DD (e.g. 2026-04-01).</p>
        <div class="row"><button class="btn primary" @click="tpl('xlsx')"><Icon name="table_view" />Excel template (.xlsx)</button><button class="btn" @click="tpl('csv')"><Icon name="description" />CSV template</button></div>
      </template>
      <template v-else-if="step === 2">
        <h3>Upload your filled file</h3>
        <label class="drop" :class="{ has: file }" @dragover.prevent @drop.prevent="e => pick(e.dataTransfer.files[0])">
          <Icon :name="file ? 'task' : 'cloud_upload'" :size="40" />
          <b>{{ file ? file.name : 'Drop the Excel/CSV file here, or click to choose' }}</b>
          <span v-if="file" class="small muted">{{ (file.size / 1024).toFixed(1) }} KB</span>
          <input type="file" hidden accept=".xlsx,.xls,.csv" @change="e => pick(e.target.files[0])" />
        </label>
        <button class="btn primary" style="margin-top:14px" :disabled="!file || busy" @click="preview"><Icon :name="busy ? 'progress_activity' : 'preview'" :class="{ spin: busy }" />Check file (nothing is saved yet)</button>
      </template>
      <template v-else>
        <h3>{{ done ? 'Import finished' : 'Check result' }}</h3>
        <div class="res stagger"><div class="ok"><b>{{ result?.created ?? 0 }}</b><span>{{ done ? 'added' : 'will be added' }}</span></div><div class="up"><b>{{ result?.updated ?? 0 }}</b><span>{{ done ? 'updated' : 'will be updated' }}</span></div><div class="bad"><b>{{ (result?.errors || []).length }}</b><span>rows with problems</span></div></div>
        <table v-if="(result?.errors || []).length" class="data" style="margin-top:12px"><thead><tr><th style="width:80px">Row</th><th>Problem</th></tr></thead><tbody><tr v-for="(e, i) in result.errors" :key="i"><td>{{ e.row }}</td><td>{{ e.message }}</td></tr></tbody></table>
        <p v-if="!done && (result?.errors || []).length" class="small muted">Rows with problems are skipped. You can fix them in Excel and import again later.</p>
        <div class="row" style="margin-top:16px">
          <button v-if="!done" class="btn success" :disabled="busy || !((result?.created || 0) + (result?.updated || 0))" @click="importNow"><Icon name="done_all" />Import now</button>
          <router-link v-if="done" class="btn primary" :to="'/m/' + mod">Open {{ current.l }}</router-link>
          <button v-if="done" class="btn" @click="reset">Import another file</button>
        </div>
      </template>
      <div class="nav"><button class="btn" :disabled="step === 0 || done" @click="step--">Back</button><span class="grow" /><button v-if="step < 2" class="btn primary" :disabled="!mod" @click="step++">Next</button></div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed } from 'vue';
import { useRoute } from 'vue-router';
import PageHead from '../../components/PageHead.vue';
import Icon from '../../components/Icon.vue';
import { api } from '../../api/client';
import { downloadFile } from '../../ui/files';
import { toast, toastError } from '../../ui/ui';
const STEPS = ['Choose', 'Template', 'Upload & check', 'Import'];
const MODULES = [
  { k: 'students', l: 'Students', i: 'school', d: 'Admission register', one: 'student' }, { k: 'parents', l: 'Parents', i: 'family_restroom', d: 'Parent contacts', one: 'parent' },
  { k: 'staff', l: 'Staff', i: 'badge', d: 'Teachers & staff', one: 'staff member' }, { k: 'books', l: 'Library books', i: 'menu_book', d: 'Accession register', one: 'book' },
  { k: 'inventory_items', l: 'Inventory', i: 'inventory_2', d: 'Stock register', one: 'item' }, { k: 'fee_structures', l: 'Fee structure', i: 'payments', d: 'Class-wise fees', one: 'fee line' },
  { k: 'transactions', l: 'Income / expense', i: 'account_balance', d: 'Cash book', one: 'entry' }, { k: 'visitors', l: 'Visitors', i: 'desk', d: 'Visitor book', one: 'visitor' },
];
const route = useRoute();
const step = ref(route.query.module ? 1 : 0), mod = ref(route.query.module || null), file = ref(null), busy = ref(false), result = ref(null), done = ref(false);
const current = computed(() => MODULES.find(m => m.k === mod.value) || { l: mod.value, one: 'record' });
const tpl = (format) => downloadFile(`import/${mod.value}/template`, { format }, { filename: `${mod.value}_template.${format}` });
function pick(f) { if (f) { file.value = f; result.value = null; } }
async function send(dry) {
  const fd = new FormData(); fd.append('file', file.value); if (dry) fd.append('dry_run', '1');
  return (await api('POST', `import/${mod.value}`, { form: fd })).data;
}
async function preview() { busy.value = true; try { result.value = await send(true); step.value = 3; } catch (e) { toastError(e); } busy.value = false; }
async function importNow() { busy.value = true; try { result.value = await send(false); done.value = true; toast(`Imported: ${result.value.created} added, ${result.value.updated} updated`); } catch (e) { toastError(e); } busy.value = false; }
function reset() { step.value = 0; file.value = null; result.value = null; done.value = false; }
</script>
<style scoped>
.steps { display: flex; gap: 8px; margin-bottom: 16px; }
.step { flex: 1; display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: 12px; background: #fff; border: 1px solid var(--border); color: var(--muted); font-weight: 600; transition: all .25s var(--ease); }
.step span { width: 26px; height: 26px; border-radius: 50%; display: grid; place-items: center; background: #f1f5f9; font-size: 12.5px; }
.step.on { border-color: var(--primary); color: var(--primary); box-shadow: 0 0 0 4px rgba(99,102,241,.1); } .step.on span { background: var(--primary); color: #fff; } .step.done span { background: var(--success); color: #fff; }
.mods { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-top: 14px; }
.mod { display: flex; flex-direction: column; gap: 6px; align-items: flex-start; padding: 16px; border-radius: 14px; border: 1.5px solid var(--border); background: #fff; cursor: pointer; font: inherit; text-align: left; color: var(--text); transition: all .2s var(--ease); }
.mod .material-icons { color: var(--primary); } .mod:hover { transform: translateY(-2px); border-color: #c7d2fe; } .mod.on { border-color: var(--primary); background: var(--primary-50); }
.drop { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 40px; border: 2px dashed #cbd5e1; border-radius: 16px; cursor: pointer; color: var(--muted); transition: all .2s; margin-top: 12px; }
.drop:hover, .drop.has { border-color: var(--primary); background: var(--primary-50); color: var(--primary); }
.res { display: flex; gap: 12px; margin-top: 12px; } .res div { flex: 1; padding: 16px; border-radius: 14px; display: flex; flex-direction: column; }
.res b { font-size: 28px; } .res .ok { background: var(--success-50); color: #065f46; } .res .up { background: var(--primary-50); color: #3730a3; } .res .bad { background: var(--danger-50); color: #9f1239; }
.nav { display: flex; margin-top: 22px; padding-top: 14px; border-top: 1px solid var(--border); }
</style>
