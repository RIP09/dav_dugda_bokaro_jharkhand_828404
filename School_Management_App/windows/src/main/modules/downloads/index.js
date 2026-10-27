// PDFs, Excel/CSV exports and backups: download to the Downloads folder (or Save As…) and open them.
const { ipcMain, app, net, dialog, shell, BrowserWindow } = require('electron');
const fs = require('fs');
const path = require('path');

function safeName(name) { return String(name || 'download').replace(/[\\/:*?"<>|]+/g, '_').slice(0, 120); }

function uniquePath(dir, name) {
  let p = path.join(dir, name); const ext = path.extname(name); const base = name.slice(0, name.length - ext.length);
  for (let i = 1; fs.existsSync(p); i++) p = path.join(dir, `${base} (${i})${ext}`);
  return p;
}

function nameFromResponse(res, fallback) {
  const cd = res.headers.get('content-disposition') || '';
  const m = /filename\*=UTF-8''([^;]+)|filename="?([^";]+)"?/i.exec(cd);
  if (m) return decodeURIComponent(m[1] || m[2]);
  return fallback;
}

async function download(win, { url, filename, saveAs = false, open = true }) {
  const res = await net.fetch(url, { headers: { Accept: '*/*' } });
  if (!res.ok) {
    let msg = `The server answered ${res.status}.`;
    try { const j = await res.json(); if (j.message) msg = j.message; } catch (_) {}
    return { ok: false, status: res.status, error: msg };
  }
  const buf = Buffer.from(await res.arrayBuffer());
  const name = safeName(nameFromResponse(res, filename));
  let target;
  if (saveAs) {
    const r = await dialog.showSaveDialog(win, { defaultPath: path.join(app.getPath('downloads'), name) });
    if (r.canceled || !r.filePath) return { ok: false, canceled: true };
    target = r.filePath;
  } else {
    target = uniquePath(app.getPath('downloads'), name);
  }
  fs.writeFileSync(target, buf);
  if (open) shell.openPath(target);
  return { ok: true, path: target, size: buf.length };
}

function registerIpc() {
  ipcMain.handle('file:download', (e, opts) => download(BrowserWindow.fromWebContents(e.sender), opts).catch(err => ({ ok: false, error: 'Download failed: ' + err.message })));
  ipcMain.handle('file:showInFolder', (_e, p) => { shell.showItemInFolder(p); return true; });
  ipcMain.handle('file:openExternal', (_e, url) => { if (/^(https?|mailto|tel):/i.test(url)) shell.openExternal(url); return true; });
}

module.exports = { registerIpc, download };
