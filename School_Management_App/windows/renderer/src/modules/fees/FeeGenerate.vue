<template>
  <div class="page">
    <PageHead title="Generate fees" sub="Create a month's fee invoices from the fee structure in one click." icon="post_add" color="#6366f1" />
    <div class="card pad" style="max-width:720px">
      <p class="muted">Invoices are made from <router-link to="/m/fee_structures">Fee structures</router-link>, minus each student's <router-link to="/m/concessions">concessions</router-link>, plus transport and hostel fees. Students who already have that month's invoice are skipped, so it is safe to run again.</p>
      <div class="row" style="margin-top:12px"><ClassSectionPicker class-only allow-all @update:class-id="v => (classId = v)" /><input v-model="month" type="month" class="input" style="width:auto" />
        <button class="btn primary" :disabled="busy" @click="go"><Icon :name="busy ? 'progress_activity' : 'bolt'" :class="{ spin: busy }" />Generate</button></div>
      <div v-if="result" class="infobar fade-up" style="margin-top:16px"><Icon name="check_circle" /><div>{{ result }} <router-link to="/m/invoices">See invoices →</router-link></div></div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue';
import PageHead from '../../components/PageHead.vue';
import Icon from '../../components/Icon.vue';
import ClassSectionPicker from '../../components/ClassSectionPicker.vue';
import { post } from '../../api/client';
import { toastError, confirmBox, toast } from '../../ui/ui';
import { thisMonth } from '../../ui/format';
const classId = ref(null), month = ref(thisMonth()), busy = ref(false), result = ref('');
async function go() {
  if (!(await confirmBox({ title: `Generate fees for ${month.value}?`, message: `Invoices will be created for ${classId.value ? 'the selected class' : 'all classes'}.`, ok: 'Generate' }))) return;
  busy.value = true; result.value = '';
  try { const r = await post('fees/generate', { class_id: classId.value || undefined, month: month.value }); result.value = r.message || `Created ${r.data?.created ?? 0} invoices.`; toast(result.value); }
  catch (e) { toastError(e); }
  busy.value = false;
}
</script>
