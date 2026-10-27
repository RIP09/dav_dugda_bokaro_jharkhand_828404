<template>
  <div class="page">
    <PageHead title="Collect fees" sub="Find the student, choose what they are paying, and print the receipt." icon="point_of_sale" color="#6366f1">
      <router-link class="btn" to="/m/payments"><Icon name="receipt_long" />All receipts</router-link>
    </PageHead>
    <div class="card pad find">
      <div class="field grow"><label>Student (name or admission no.)</label>
        <RefPicker v-model="studentId" :ref-info="{ module: 'students', label_field: 'name' }" placeholder="Search student…" @picked="s => (student = s)" /></div>
    </div>
    <div v-if="!studentId" class="card"><EmptyState icon="person_search" title="Find a student to begin" text="Their pending fees will show here." /></div>
    <div v-else class="cols">
      <div class="card">
        <div class="card-head"><Icon name="receipt" /><h3>Fees for {{ student?._label || student?.name }}</h3><span class="grow" />
          <button class="btn sm" @click="openPdf('fee-statement', studentId, {}, 'Fee_statement')"><Icon name="description" />Statement</button></div>
        <div v-if="loading" style="padding:16px"><div class="skeleton" style="height:200px" /></div>
        <table v-else class="data">
          <thead><tr><th style="width:40px" /><th>Fee</th><th>Due date</th><th class="right">Amount</th><th class="right">Paid</th><th class="right">Balance</th><th>Status</th></tr></thead>
          <tbody>
            <tr v-for="i in invoices" :key="i.id" class="click" :class="{ selrow: selected?.id === i.id }" @click="choose(i)">
              <td><input type="radio" :checked="selected?.id === i.id" :disabled="!(i.balance > 0)" /></td>
              <td><b>{{ i.title }}</b><div class="small faint">{{ i.invoice_no }}</div></td>
              <td :class="{ overdue: overdue(i) }">{{ date(i.due_date) }}</td>
              <td class="right">{{ money(i.amount - (i.discount || 0)) }}</td><td class="right">{{ money(i.paid) }}</td><td class="right"><b>{{ money(i.balance) }}</b></td>
              <td><span class="badge" :class="i.status === 'paid' ? 'green' : i.status === 'partial' ? 'amber' : 'red'">{{ i.status }}</span></td>
            </tr>
          </tbody>
        </table>
        <EmptyState v-if="!loading && !invoices.length" icon="task_alt" title="No fees found" text="No invoices for this student yet. Use Generate fees, or add an invoice under Fees → Invoices." />
        <div class="tot"><span>Total due</span><b>{{ money(totalDue) }}</b></div>
      </div>
      <div class="card pad pay">
        <h3>Receive payment</h3>
        <template v-if="selected">
          <div class="muted small" style="margin:4px 0 14px">{{ selected.title }} · balance {{ money(selected.balance) }}</div>
          <div class="field"><label>Amount (₹)</label><input v-model.number="amount" type="number" class="input big" :max="selected.balance" min="1" /></div>
          <div class="field" style="margin-top:12px"><label>Paid by</label>
            <div class="methods"><button v-for="m in METHODS" :key="m.v" class="chip" :class="{ active: method === m.v }" @click="method = m.v"><Icon :name="m.i" :size="16" />{{ m.l }}</button></div></div>
          <div v-if="method !== 'cash'" class="field" style="margin-top:12px"><label>Reference (UPI ref / cheque no.)</label><input v-model="reference" class="input" /></div>
          <div class="field" style="margin-top:12px"><label>Date</label><input v-model="paidOn" type="date" class="input" /></div>
          <button class="btn primary wide" :disabled="busy || !(amount > 0) || amount > selected.balance + 0.001" @click="pay"><Icon :name="busy ? 'progress_activity' : 'payments'" :class="{ spin: busy }" />Receive {{ money(amount || 0) }}</button>
          <p class="small muted">A receipt is issued immediately and becomes a permanent record. Mistakes are corrected by cancelling the receipt (with a reason) and collecting again.</p>
        </template>
        <p v-else class="muted">Select a fee on the left.</p>
        <transition name="pop">
          <div v-if="receipt" class="receipt">
            <Icon name="check_circle" :size="34" class="ok" />
            <div><b>Receipt {{ receipt.receipt_no }}</b><div class="small muted">{{ money(receipt.amount) }} · {{ receipt.method }}</div></div>
            <div class="row" style="margin-top:10px">
              <button class="btn primary sm" @click="printPdf('receipt', receipt.id, {}, 'Receipt ' + receipt.receipt_no)"><Icon name="print" />Print</button>
              <button class="btn sm" @click="openPdf('receipt', receipt.id, {}, 'Receipt_' + receipt.receipt_no)"><Icon name="picture_as_pdf" />Save PDF</button>
            </div>
          </div>
        </transition>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, watch } from 'vue';
import PageHead from '../../components/PageHead.vue';
import Icon from '../../components/Icon.vue';
import EmptyState from '../../components/EmptyState.vue';
import RefPicker from '../generic/RefPicker.vue';
import { get, post } from '../../api/client';
import { toast, toastError } from '../../ui/ui';
import { openPdf, printPdf } from '../../ui/files';
import { money, date, today } from '../../ui/format';
const METHODS = [{ v: 'cash', l: 'Cash', i: 'payments' }, { v: 'upi', l: 'UPI', i: 'qr_code_2' }, { v: 'bank', l: 'Bank', i: 'account_balance' }, { v: 'cheque', l: 'Cheque', i: 'description' }, { v: 'card', l: 'Card', i: 'credit_card' }];
const studentId = ref(null), student = ref(null), invoices = ref([]), loading = ref(false), selected = ref(null);
const amount = ref(null), method = ref('cash'), reference = ref(''), paidOn = ref(today()), busy = ref(false), receipt = ref(null);
const totalDue = computed(() => invoices.value.reduce((a, i) => a + Number(i.balance || 0), 0));
const overdue = (i) => i.balance > 0 && i.due_date && i.due_date < today();
async function load() {
  receipt.value = null; selected.value = null; if (!studentId.value) return;
  loading.value = true;
  try { invoices.value = (await get('invoices', { student_id: studentId.value })).data || []; const first = invoices.value.find(i => i.balance > 0); if (first) choose(first); }
  catch (e) { toastError(e); invoices.value = []; }
  loading.value = false;
}
watch(studentId, load);
function choose(i) { if (!(i.balance > 0)) return; selected.value = i; amount.value = Number(i.balance); }
async function pay() {
  busy.value = true;
  try {
    const r = await post(`invoices/${selected.value.id}/payments`, { amount: amount.value, method: method.value, reference: reference.value || null, paid_on: paidOn.value });
    receipt.value = r.data.payment; toast(r.message || 'Payment recorded');
    const keep = receipt.value; await load(); receipt.value = keep; reference.value = '';
  } catch (e) { toastError(e); }
  busy.value = false;
}
</script>
<style scoped>
.find { margin-bottom: 16px; display: flex; }
.cols { display: grid; grid-template-columns: 1fr 360px; gap: 16px; align-items: start; }
.selrow td { background: var(--primary-50) !important; }
.overdue { color: var(--danger); font-weight: 600; }
.tot { display: flex; justify-content: flex-end; gap: 14px; align-items: center; padding: 14px 18px; border-top: 1px solid var(--border); font-size: 15px; }
.tot b { font-size: 20px; }
.big { height: 46px; font-size: 20px; font-weight: 700; }
.methods { display: flex; gap: 6px; flex-wrap: wrap; }
.wide { width: 100%; justify-content: center; height: 44px; margin: 18px 0 10px; font-size: 15px; }
.receipt { margin-top: 16px; padding: 16px; border-radius: 14px; background: var(--success-50); border: 1px solid #a7f3d0; }
.ok { color: var(--success); }
</style>
