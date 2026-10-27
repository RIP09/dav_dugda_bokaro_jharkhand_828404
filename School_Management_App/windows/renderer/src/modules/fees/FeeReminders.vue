<template>
  <div class="page">
    <PageHead title="Fee reminders & generation" sub="Remind parents about dues (in-app + free WhatsApp links) and create monthly fee invoices." icon="notifications_active" color="#f59e0b" />
    <div class="cols">
      <div class="card pad">
        <h3><Icon name="campaign" /> Send due reminders</h3>
        <p class="muted small">Parents get a notification in their app. You also get a WhatsApp link for each parent — click to open WhatsApp with the message ready (free).</p>
        <ClassSectionPicker v-model="sectionId" allow-all @update:class-id="v => (classId = v)" />
        <button class="btn primary" style="margin-top:14px" :disabled="busy" @click="remind"><Icon :name="busy ? 'progress_activity' : 'send'" :class="{ spin: busy }" />Send reminders</button>
      </div>
      <div class="card pad">
        <h3><Icon name="post_add" /> Generate fees</h3>
        <p class="muted small">Creates invoices for the month from the fee structure (minus concessions, plus transport/hostel fees). Students who already have that month's invoice are skipped.</p>
        <div class="row"><ClassSectionPicker class-only allow-all @update:class-id="v => (genClass = v)" /><input v-model="genMonth" type="month" class="input" style="width:auto" /></div>
        <button class="btn primary" style="margin-top:14px" :disabled="genBusy" @click="generate"><Icon name="bolt" />Generate</button>
        <p v-if="genResult" class="ok small">{{ genResult }}</p>
      </div>
    </div>
    <div v-if="list.length" class="card fade-up" style="margin-top:16px">
      <div class="card-head"><h3>{{ list.length }} parents with dues</h3><span class="grow" /><span class="muted small">Total {{ money(list.reduce((a, x) => a + Number(x.due || x.amount_due || 0), 0)) }}</span></div>
      <table class="data">
        <thead><tr><th>Parent</th><th>Student(s)</th><th>Phone</th><th class="right">Due</th><th /></tr></thead>
        <tbody><tr v-for="(p, i) in list" :key="i">
          <td><b>{{ p.parent_name || p.name }}</b></td><td>{{ p.students || p.student_names || p.student_name }}</td><td>{{ p.phone }}</td><td class="right"><b>{{ money(p.due || p.amount_due) }}</b></td>
          <td class="right"><button v-if="p.whatsapp_url" class="btn sm wa" @click="openExternal(p.whatsapp_url)"><Icon name="chat" />WhatsApp</button></td>
        </tr></tbody>
      </table>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue';
import PageHead from '../../components/PageHead.vue';
import Icon from '../../components/Icon.vue';
import ClassSectionPicker from '../../components/ClassSectionPicker.vue';
import { post } from '../../api/client';
import { toast, toastError, confirmBox } from '../../ui/ui';
import { openExternal } from '../../ui/files';
import { money, thisMonth } from '../../ui/format';
const sectionId = ref(null), classId = ref(null), busy = ref(false), list = ref([]);
const genClass = ref(null), genMonth = ref(thisMonth()), genBusy = ref(false), genResult = ref('');
async function remind() {
  busy.value = true;
  try { const r = await post('fees/reminders', { class_id: classId.value || undefined, section_id: sectionId.value || undefined }); list.value = r.data?.parents || r.data || []; toast(r.message || `Reminders sent to ${list.value.length} parents`); }
  catch (e) { toastError(e); }
  busy.value = false;
}
async function generate() {
  if (!(await confirmBox({ title: 'Generate fees for ' + genMonth.value + '?', message: 'Invoices will be created for ' + (genClass.value ? 'the selected class' : 'all classes') + '.', ok: 'Generate' }))) return;
  genBusy.value = true; genResult.value = '';
  try { const r = await post('fees/generate', { class_id: genClass.value || undefined, month: genMonth.value }); genResult.value = r.message || ('Created ' + (r.data?.created ?? '') + ' invoices'); toast(genResult.value); }
  catch (e) { toastError(e); }
  genBusy.value = false;
}
</script>
<style scoped>
.cols { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
h3 { display: flex; gap: 8px; align-items: center; margin-bottom: 6px; }
p { line-height: 1.5; } .ok { color: var(--success); font-weight: 600; }
.wa { background: #25d366; color: #fff; border-color: transparent; }
</style>
