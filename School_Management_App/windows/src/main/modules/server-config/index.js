// Server address + small settings, stored as JSON in the user's AppData folder.
const { app, ipcMain, net } = require('electron');
const fs = require('fs');
const path = require('path');

const DEMO_SERVER = 'https://dav-dugda-sms.sites.tab.bot/api/v1/';
let file = null;
let data = { serverUrl: null, bounds: null, maximized: false, zoom: 0, token: null, user: null, firstRunDone: false };

function normalize(url) {
  let u = String(url || '').trim();
  if (!u) return '';
  if (!/^https?:\/\//i.test(u)) u = (/^(localhost|127\.|192\.168\.|10\.)/.test(u) ? 'http://' : 'https://') + u;
  u = u.replace(/\/+$/, '');
  if (!/\/api\/v1$/.test(u)) u = u.replace(/\/api$/, '') + '/api/v1';
  return u + '/';
}

function init() {
  file = path.join(app.getPath('userData'), 'settings.json');
  try { data = { ...data, ...JSON.parse(fs.readFileSync(file, 'utf8')) }; } catch (_) { /* first run */ }
  if (process.env.DAV_SERVER_URL) data.serverUrl = normalize(process.env.DAV_SERVER_URL);
}
function save() {
  try { fs.mkdirSync(path.dirname(file), { recursive: true }); fs.writeFileSync(file, JSON.stringify(data, null, 2)); } catch (e) { console.error('settings save failed', e); }
}
function get(key) { return data[key]; }
function set(key, value) { data[key] = value; save(); }

async function testConnection(url) {
  const base = normalize(url);
  if (!base) return { ok: false, error: 'Please type the server address.' };
  try {
    const res = await net.fetch(base + 'school', { headers: { Accept: 'application/json' } });
    if (!res.ok) return { ok: false, error: `The server answered ${res.status}. Check the address.` };
    const json = await res.json();
    const d = json.data || {};
    return { ok: true, url: base, school: d.name || d.school_name || 'School server' };
  } catch (e) {
    return { ok: false, error: 'Could not reach the server. Check the address and your internet. (' + e.message + ')' };
  }
}

function registerIpc() {
  ipcMain.handle('config:get', () => ({ serverUrl: data.serverUrl, demoServer: DEMO_SERVER, token: data.token, user: data.user, firstRunDone: data.firstRunDone, version: app.getVersion() }));
  ipcMain.handle('config:setServer', (_e, url) => { data.serverUrl = normalize(url); data.firstRunDone = true; save(); return data.serverUrl; });
  ipcMain.handle('config:setSession', (_e, token, user) => { data.token = token || null; data.user = user || null; save(); return true; });
  ipcMain.handle('config:test', (_e, url) => testConnection(url));
}

module.exports = { init, get, set, save, registerIpc, normalize, DEMO_SERVER };
