<template>
  <div class="page">
    <PageHead title="Payroll" sub="Create the month's salary slips from salary structures, check them, then mark as paid." icon="request_quote" color="#0ea5e9">
      <input v-model="month" type="month" class="input" style="width:auto" />
      <button class="btn primary" :disabled="busy" @click="run"><Icon :name="busy ? 'progress_activity' : 'bolt'" :class="{ spin: busy }" />Prepare slips for {{ month }}</button>
    </PageHead>
    <div class="infobar" style="margin-bottom:14px"><Icon name="info" /><div>Slips start as <b>draft</b> and can be edited. Once marked <b>paid</b> a slip is locked permanently.</div></div>
    <ModuleView v-if="app.modules.payslips" :key="k" module-key="payslips" />
    <div v-else class="card"><EmptyState icon="request_quote" title="Payroll is not available for your login" /></div>
  </div>
</template>
<script setup>
import { ref } from 'vue';
import PageHead from '../../components/PageHead.vue';
import Icon from '../../components/Icon.vue';
import EmptyState from '../../components/EmptyState.vue';
import ModuleView from '../generic/ModuleView.vue';
import { post } from '../../api/client';
import { app } from '../../state';
import { toast, toastError, confirmBox } from '../../ui/ui';
import { thisMonth } from '../../ui/format';
const month = ref(thisMonth()), busy = ref(false), k = ref(0);
async function run() {
  if (!(await confirmBox({ title: 'Prepare salary slips?', message: `Draft slips for ${month.value} will be created for every staff member with a salary structure.`, ok: 'Prepare' }))) return;
  busy.value = true;
  try { const r = await post('payroll/run', { month: month.value }); toast(r.message || 'Slips prepared'); k.value++; }
  catch (e) { toastError(e); }
  busy.value = false;
}
</script>
<style scoped>:deep(.page) { padding: 0; }</style>
