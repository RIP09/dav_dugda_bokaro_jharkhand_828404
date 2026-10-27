// Update check against the GitHub repository's releases (free, no account needed). Only informs; never auto-installs.
const { app, net, dialog, shell, ipcMain, BrowserWindow } = require('electron');

const REPO = 'RIP09/dav_dugda_bokaro_jharkhand_828404';

function newer(a, b) {
  const pa = String(a).replace(/^v/, '').split('.').map(Number), pb = String(b).replace(/^v/, '').split('.').map(Number);
  for (let i = 0; i < 3; i++) { if ((pa[i] || 0) > (pb[i] || 0)) return true; if ((pa[i] || 0) < (pb[i] || 0)) return false; }
  return false;
}

async function latest() {
  const res = await net.fetch(`https://api.github.com/repos/${REPO}/releases/latest`, { headers: { Accept: 'application/vnd.github+json', 'User-Agent': 'DAV-School-Management' } });
  if (!res.ok) return null;
  const j = await res.json();
  return { version: (j.tag_name || '').replace(/^v/, ''), url: j.html_url };
}

async function check(win, interactive) {
  try {
    const l = await latest();
    if (l && l.version && newer(l.version, app.getVersion())) {
      const r = await dialog.showMessageBox(win, { type: 'info', message: `Version ${l.version} is available`, detail: `You have ${app.getVersion()}. Open the download page?`, buttons: ['Download', 'Later'] });
      if (r.response === 0) shell.openExternal(l.url);
      return { update: true, ...l };
    }
    if (interactive) dialog.showMessageBox(win, { type: 'info', message: 'You have the latest version', detail: 'DAV School Management ' + app.getVersion() });
    return { update: false };
  } catch (e) {
    if (interactive) dialog.showMessageBox(win, { type: 'warning', message: 'Could not check for updates', detail: e.message });
    return { update: false, error: e.message };
  }
}

function registerIpc() {
  ipcMain.handle('app:checkUpdates', (e) => check(BrowserWindow.fromWebContents(e.sender), true));
}

module.exports = { check, registerIpc };
