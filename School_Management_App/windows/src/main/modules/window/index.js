// Main window: remembers size/position, opens external links in the default browser.
const { BrowserWindow, shell, screen, app } = require('electron');
const path = require('path');
const config = require('../server-config');
const offline = require('../offline');

let win = null;

function iconPath() {
  return path.join(__dirname, '..', '..', '..', '..', 'build', process.platform === 'win32' ? 'icon.ico' : 'icon.png');
}

function boundsVisible(b) {
  if (!b) return false;
  return screen.getAllDisplays().some(d => {
    const a = d.workArea;
    return b.x < a.x + a.width - 50 && b.x + b.width > a.x + 50 && b.y >= a.y - 20 && b.y < a.y + a.height - 50;
  });
}

function create() {
  const saved = config.get('bounds');
  const opts = {
    width: 1360, height: 860, minWidth: 1024, minHeight: 640,
    show: false, backgroundColor: '#0f172a', title: 'DAV School Management',
    icon: iconPath(),
    autoHideMenuBar: false,
    webPreferences: {
      preload: path.join(__dirname, '..', '..', '..', 'preload', 'index.js'),
      contextIsolation: true, nodeIntegration: false, sandbox: true, spellcheck: true,
    },
  };
  if (boundsVisible(saved)) Object.assign(opts, saved);
  win = new BrowserWindow(opts);
  if (config.get('maximized')) win.maximize();

  const save = () => {
    if (!win || win.isDestroyed()) return;
    config.set('maximized', win.isMaximized());
    if (!win.isMaximized() && !win.isMinimized() && !win.isFullScreen()) config.set('bounds', win.getBounds());
  };
  win.on('resize', debounce(save, 400));
  win.on('move', debounce(save, 400));
  win.on('close', save);
  win.once('ready-to-show', () => win.show());

  // Links that leave the app (wa.me, maps, websites) open in the normal browser.
  win.webContents.setWindowOpenHandler(({ url }) => {
    if (/^https?:|^mailto:|^tel:/i.test(url)) shell.openExternal(url);
    return { action: 'deny' };
  });
  win.webContents.on('will-navigate', (e, url) => {
    if (!url.startsWith('file:')) { e.preventDefault(); shell.openExternal(url); }
  });
  win.webContents.on('did-finish-load', () => {
    const z = config.get('zoom'); if (z) win.webContents.setZoomLevel(z);
  });
  offline.attach(win);

  win.loadFile(path.join(app.getAppPath(), 'renderer-dist', 'index.html'));
  return win;
}

function focus() {
  if (!win) return;
  if (win.isMinimized()) win.restore();
  win.show(); win.focus();
}

function get() { return win; }

function debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }

module.exports = { create, focus, get, iconPath };
