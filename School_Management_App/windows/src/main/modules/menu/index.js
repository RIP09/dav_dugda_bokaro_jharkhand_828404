// Native menu bar (File / View / Help) and keyboard shortcuts.
const { Menu, app, shell } = require('electron');
const config = require('../server-config');
const about = require('../about');
const updates = require('../updates');

function send(win, channel, ...args) { if (win && !win.isDestroyed()) win.webContents.send(channel, ...args); }

function zoom(win, delta) {
  const wc = win.webContents;
  const z = delta === 0 ? 0 : Math.max(-3, Math.min(4, wc.getZoomLevel() + delta));
  wc.setZoomLevel(z); config.set('zoom', z);
}

function build(win) {
  const template = [
    { label: '&File', submenu: [
      { label: 'Print this page', accelerator: 'CmdOrCtrl+P', click: () => send(win, 'menu:print') },
      { label: 'Search…', accelerator: 'CmdOrCtrl+K', click: () => send(win, 'menu:search') },
      { type: 'separator' },
      { label: 'Server settings…', click: () => send(win, 'menu:navigate', '/setup') },
      { label: 'School settings', click: () => send(win, 'menu:navigate', '/s/settings') },
      { label: 'Open Downloads folder', click: () => shell.openPath(app.getPath('downloads')) },
      { type: 'separator' },
      { label: 'Sign out', click: () => send(win, 'menu:logout') },
      { label: 'Exit', accelerator: 'Alt+F4', role: 'quit' },
    ] },
    { label: '&Edit', submenu: [
      { role: 'undo' }, { role: 'redo' }, { type: 'separator' },
      { role: 'cut' }, { role: 'copy' }, { role: 'paste' }, { role: 'selectAll' },
    ] },
    { label: '&View', submenu: [
      { label: 'Dashboard', accelerator: 'CmdOrCtrl+D', click: () => send(win, 'menu:navigate', '/dashboard') },
      { label: 'Reload', accelerator: 'CmdOrCtrl+R', click: () => send(win, 'menu:reload') },
      { label: 'Reload (F5)', accelerator: 'F5', visible: false, click: () => send(win, 'menu:reload') },
      { type: 'separator' },
      { label: 'Zoom in', accelerator: 'CmdOrCtrl+=', click: () => zoom(win, 0.5) },
      { label: 'Zoom out', accelerator: 'CmdOrCtrl+-', click: () => zoom(win, -0.5) },
      { label: 'Actual size', accelerator: 'CmdOrCtrl+0', click: () => zoom(win, 0) },
      { type: 'separator' },
      { label: 'Full screen', accelerator: 'F11', click: () => win.setFullScreen(!win.isFullScreen()) },
      { label: 'Developer tools', accelerator: 'CmdOrCtrl+Shift+I', click: () => win.webContents.toggleDevTools() },
    ] },
    { label: '&Help', submenu: [
      { label: 'User guide', click: () => shell.openExternal('https://github.com/RIP09/dav_dugda_bokaro_jharkhand_828404/tree/main/School_Management_App') },
      { label: 'Check for updates', click: () => updates.check(win, true) },
      { type: 'separator' },
      { label: 'About DAV School Management', click: () => about.show(win) },
    ] },
  ];
  Menu.setApplicationMenu(Menu.buildFromTemplate(template));
}

module.exports = { build };
