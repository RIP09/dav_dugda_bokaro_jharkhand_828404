<template>
  <div class="page">
    <PageHead title="Messages" sub="Private messages between school, teachers and parents." icon="forum" color="#0ea5e9">
      <button class="btn primary" @click="composeOpen = true"><Icon name="edit" />New message</button>
    </PageHead>
    <div class="layout">
      <div class="card list">
        <div class="tabs"><div class="tab" :class="{ active: box === 'inbox' }" @click="box = 'inbox'">Inbox</div><div class="tab" :class="{ active: box === 'sent' }" @click="box = 'sent'">Sent</div></div>
        <div v-if="loading" style="padding:12px"><div v-for="i in 5" :key="i" class="skeleton" style="height:52px;margin-bottom:8px" /></div>
        <div v-for="m in messages" :key="m.id" class="msg" :class="{ on: sel?.id === m.id, unread: box === 'inbox' && !m.read_at }" @click="open(m)">
          <div class="av">{{ ini(other(m)?.name) }}</div>
          <div class="grow" style="min-width:0"><div class="row"><b class="nm">{{ other(m)?.name }}</b><span class="grow" /><span class="faint small">{{ ago(m.created_at) }}</span></div>
            <div class="subj">{{ m.subject || '(no subject)' }}</div><div class="prev small muted">{{ m.body }}</div></div>
        </div>
        <EmptyState v-if="!loading && !messages.length" icon="forum" title="No messages" />
      </div>
      <div class="card view">
        <EmptyState v-if="!sel" icon="mark_email_unread" title="Select a message" />
        <div v-else class="fade-up" :key="sel.id" style="padding:22px">
          <div class="small muted">{{ box === 'inbox' ? 'From' : 'To' }} <b>{{ other(sel)?.name }}</b> · {{ datetime(sel.created_at) }}</div>
          <h2 style="margin:8px 0 16px">{{ sel.subject || '(no subject)' }}</h2>
          <div class="body">{{ sel.body }}</div>
          <button v-if="box === 'inbox'" class="btn" style="margin-top:20px" @click="reply"><Icon name="reply" />Reply</button>
        </div>
      </div>
    </div>
    <Modal :open="composeOpen" title="New message" icon="edit" @close="composeOpen = false">
      <div class="field"><label>To</label><select v-model.number="draft.receiver_id" class="input"><option :value="null">Choose</option><option v-for="c in contacts" :key="c.id" :value="c.id">{{ c.name }} ({{ c.role }})</option></select></div>
      <div class="field" style="margin-top:12px"><label>Subject</label><input v-model="draft.subject" class="input" /></div>
      <div class="field" style="margin-top:12px"><label>Message</label><textarea v-model="draft.body" class="input" rows="6" /></div>
      <template #footer><button class="btn" @click="composeOpen = false">Cancel</button><button class="btn primary" :disabled="!draft.receiver_id || !draft.body" @click="send"><Icon name="send" />Send</button></template>
    </Modal>
  </div>
</template>
<script setup>
import { ref, reactive, watch, onMounted } from 'vue';
import PageHead from '../../components/PageHead.vue';
import Icon from '../../components/Icon.vue';
import EmptyState from '../../components/EmptyState.vue';
import Modal from '../../components/Modal.vue';
import { get, post } from '../../api/client';
import { toast, toastError } from '../../ui/ui';
import { ago, datetime } from '../../ui/format';
const box = ref('inbox'), messages = ref([]), sel = ref(null), loading = ref(false), composeOpen = ref(false), contacts = ref([]);
const draft = reactive({ receiver_id: null, subject: '', body: '' });
const other = (m) => (box.value === 'inbox' ? m.sender : m.receiver);
const ini = (n) => (n || '?').split(' ').map(s => s[0]).slice(0, 2).join('');
async function load() { loading.value = true; sel.value = null; try { messages.value = (await get('messages', { box: box.value })).data || []; } catch (e) { toastError(e); } loading.value = false; }
watch(box, load); onMounted(async () => { load(); try { contacts.value = (await get('messages/contacts')).data || []; } catch (_) {} });
async function open(m) { sel.value = m; if (box.value === 'inbox' && !m.read_at) { get('messages/' + m.id).catch(() => {}); m.read_at = new Date().toISOString(); } }
function reply() { draft.receiver_id = sel.value.sender_id || sel.value.sender?.id; draft.subject = 'Re: ' + (sel.value.subject || ''); draft.body = ''; composeOpen.value = true; }
async function send() {
  try { await post('messages', { ...draft }); toast('Message sent'); composeOpen.value = false; Object.assign(draft, { receiver_id: null, subject: '', body: '' }); if (box.value === 'sent') load(); }
  catch (e) { toastError(e); }
}
</script>
<style scoped>
.layout { display: grid; grid-template-columns: 380px 1fr; gap: 16px; height: calc(100vh - 170px); }
.list, .view { overflow: auto; }
.msg { display: flex; gap: 12px; padding: 12px 14px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background .12s; }
.msg:hover { background: var(--panel-2); } .msg.on { background: var(--primary-50); }
.msg.unread .nm, .msg.unread .subj { font-weight: 750; }
.av { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #a5b4fc, #f0abfc); color: #fff; display: grid; place-items: center; font-weight: 700; font-size: 13px; flex: none; }
.subj { font-size: 13.5px; margin-top: 2px; } .prev { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.body { white-space: pre-wrap; line-height: 1.6; font-size: 14.5px; }
</style>
