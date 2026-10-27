<template>
  <div class="page">
    <PageHead title="Backup" sub="Download a complete copy of all school data to keep safe." icon="backup" color="#0ea5e9" />
    <div class="card pad hero fade-up">
      <div class="big"><Icon name="cloud_download" :size="44" /></div>
      <div class="grow"><h2>Download full backup</h2><p class="muted">A .zip with every table as Excel-friendly CSV and JSON (and the database file when SQLite is used). Keep it on a pen drive or Google Drive. Do this every week.</p></div>
      <div class="col"><button class="btn primary" :disabled="busy" @click="go(false)"><Icon name="download" />Download to Downloads</button><button class="btn" :disabled="busy" @click="go(true)"><Icon name="save_as" />Save as…</button></div>
    </div>
    <div v-if="last" class="infobar" style="margin-top:14px"><Icon name="check_circle" /><div>Last backup saved to <b>{{ last.path }}</b> ({{ (last.size / 1024 / 1024).toFixed(2) }} MB). <a v-if="isDesktop" href="#" @click.prevent="desktop.showInFolder(last.path)">Show in folder</a></div></div>
    <div class="card pad" style="margin-top:16px">
      <h3>Restore</h3>
      <p class="muted small">For safety, restoring a backup is done on the server by the person who manages it (see the README: "Backup &amp; restore"). Restoring from this app is not offered.</p>
      <h3 style="margin-top:14px">Export one section</h3>
      <p class="muted small">Every list (students, fees, books…) has an Export button for Excel, CSV or PDF.</p>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue';
import PageHead from '../../components/PageHead.vue';
import Icon from '../../components/Icon.vue';
import { isDesktop, desktop } from '../../api/client';
import { downloadFile } from '../../ui/files';
const busy = ref(false), last = ref(null);
async function go(saveAs) {
  busy.value = true;
  const stamp = new Date().toISOString().slice(0, 16).replace(/[:T]/g, '-');
  const r = await downloadFile('backup/download', {}, { filename: `DAV_backup_${stamp}.zip`, saveAs, open: false });
  if (r?.ok && r.path) last.value = r;
  busy.value = false;
}
</script>
<style scoped>
.hero { display: flex; gap: 20px; align-items: center; }
.big { width: 84px; height: 84px; border-radius: 24px; display: grid; place-items: center; background: linear-gradient(135deg, #e0f2fe, #e0e7ff); color: #0284c7; }
.col { display: flex; flex-direction: column; gap: 8px; }
p { line-height: 1.5; }
</style>
