// API client for the DAV School Management server (contract: docs/API_V2_CONTRACT.md).
import { reactive } from 'vue';

const desktop = window.desktop || null;
export const DEMO_SERVER = 'https://dav-dugda-sms.sites.tab.bot/api/v1/';
export const session = reactive({ server: '', token: null, user: null, ready: false, version: '2.0.1', firstRunDone: false });

const listeners = { unauthorized: [] };
export function onUnauthorized(fn) { listeners.unauthorized.push(fn); }

export class ApiError extends Error {
  constructor(status, message, errors = null, body = null) { super(message); this.status = status; this.errors = errors; this.body = body; }
  get locked() { return this.status === 423; }
}

export function normalizeServer(url) {
  let u = String(url || '').trim();
  if (!u) return '';
  if (!/^https?:\/\//i.test(u)) u = (/^(localhost|127\.|192\.168\.|10\.)/.test(u) ? 'http://' : 'https://') + u;
  u = u.replace(/\/+$/, '');
  if (!/\/api\/v1$/.test(u)) u = u.replace(/\/api$/, '') + '/api/v1';
  return u + '/';
}

export async function initSession() {
  const qs = new URLSearchParams(location.search);
  if (desktop) {
    const c = await desktop.getConfig();
    Object.assign(session, { server: c.serverUrl || '', token: c.token, user: c.user, version: c.version, firstRunDone: c.firstRunDone });
  } else {
    session.server = localStorage.getItem('dav.server') || '';
    session.token = localStorage.getItem('dav.token');
    try { session.user = JSON.parse(localStorage.getItem('dav.user') || 'null'); } catch (_) {}
    session.firstRunDone = !!session.server;
  }
  if (qs.get('server')) { session.server = normalizeServer(qs.get('server')); session.firstRunDone = true; }
  session.ready = true;
}

export async function setServer(url) {
  const u = normalizeServer(url);
  session.server = u; session.firstRunDone = true;
  if (desktop) await desktop.setServer(u); else localStorage.setItem('dav.server', u);
}

export async function saveSession(token, user) {
  session.token = token; session.user = user;
  if (desktop) await desktop.setSession(token, user);
  else { token ? localStorage.setItem('dav.token', token) : localStorage.removeItem('dav.token'); localStorage.setItem('dav.user', JSON.stringify(user)); }
}

export async function testServer(url) {
  if (desktop) return desktop.testServer(url);
  const base = normalizeServer(url);
  try {
    const r = await fetch(base + 'school', { headers: { Accept: 'application/json' } });
    if (!r.ok) return { ok: false, error: `The server answered ${r.status}.` };
    const j = await r.json(); return { ok: true, url: base, school: j.data?.name || j.data?.school_name };
  } catch (e) { return { ok: false, error: 'Could not reach the server. ' + e.message }; }
}

function buildQuery(query) {
  if (!query) return '';
  const p = new URLSearchParams();
  const add = (k, v) => { if (v === undefined || v === null || v === '') return; p.append(k, v); };
  for (const [k, v] of Object.entries(query)) {
    if (v && typeof v === 'object' && !Array.isArray(v)) { for (const [k2, v2] of Object.entries(v)) add(`${k}[${k2}]`, v2); }
    else if (Array.isArray(v)) v.forEach(x => add(k + '[]', x));
    else add(k, v);
  }
  const s = p.toString();
  return s ? '?' + s : '';
}

export const netState = reactive({ offline: false, lastError: '' });

export async function api(method, path, { body, query, form, raw } = {}) {
  if (!session.server) throw new ApiError(0, 'No server is set. Open Server settings.');
  const url = session.server + path.replace(/^\//, '') + buildQuery(query);
  const headers = { Accept: 'application/json' };
  if (session.token) headers.Authorization = 'Bearer ' + session.token;
  let payload;
  if (form) payload = form;
  else if (body !== undefined) { headers['Content-Type'] = 'application/json'; payload = JSON.stringify(body); }
  let res;
  try {
    res = await fetch(url, { method, headers, body: payload });
  } catch (e) {
    netState.offline = true; netState.lastError = e.message;
    throw new ApiError(0, 'Cannot reach the server. Check your internet connection.');
  }
  netState.offline = false;
  if (raw) return res;
  let json = null;
  const text = await res.text();
  try { json = text ? JSON.parse(text) : null; } catch (_) { json = null; }
  if (!res.ok) {
    if (res.status === 401 && session.token && !path.startsWith('auth/login')) listeners.unauthorized.forEach(f => f());
    let msg = json?.message || `Request failed (${res.status})`;
    if (res.status === 423 && !json?.message) msg = 'This record is locked and cannot be changed.';
    if (res.status === 403 && !json?.message) msg = 'You do not have permission for this.';
    if (res.status === 404 && (!json?.message || /route .* could not be found/i.test(json.message))) msg = 'This feature is not available on the server yet.';
    throw new ApiError(res.status, msg, json?.errors || null, json);
  }
  return json;
}

export const get = (p, query) => api('GET', p, { query });
export const post = (p, body) => api('POST', p, { body: body ?? {} });
export const put = (p, body) => api('PUT', p, { body });
export const del = (p) => api('DELETE', p);
export const upload = (p, file, field = 'file', extra = {}) => {
  const fd = new FormData(); fd.append(field, file);
  for (const [k, v] of Object.entries(extra)) fd.append(k, v);
  return api('POST', p, { form: fd });
};

/** Absolute URL for a file endpoint (PDF/export/backup), token in the query as the contract allows. */
export function fileUrl(path, query = {}) {
  return session.server + path.replace(/^\//, '') + buildQuery({ ...query, token: session.token });
}

export const isDesktop = !!desktop;
export { desktop };
