// Safe bridge between the management UI and the desktop features.
const { contextBridge, ipcRenderer } = require('electron');

const on = (channel) => (fn) => { const h = (_e, ...a) => fn(...a); ipcRenderer.on(channel, h); return () => ipcRenderer.removeListener(channel, h); };

contextBridge.exposeInMainWorld('desktop', {
  isDesktop: true,
  platform: process.platform,
  getConfig: () => ipcRenderer.invoke('config:get'),
  setServer: (url) => ipcRenderer.invoke('config:setServer', url),
  setSession: (token, user) => ipcRenderer.invoke('config:setSession', token, user),
  testServer: (url) => ipcRenderer.invoke('config:test', url),
  netStatus: (url) => ipcRenderer.invoke('net:status', url),
  download: (opts) => ipcRenderer.invoke('file:download', opts),
  showInFolder: (p) => ipcRenderer.invoke('file:showInFolder', p),
  openExternal: (url) => ipcRenderer.invoke('file:openExternal', url),
  printPage: () => ipcRenderer.invoke('print:page'),
  printPdf: (url, title) => ipcRenderer.invoke('print:pdf', { url, title }),
  about: () => ipcRenderer.invoke('app:about'),
  checkUpdates: () => ipcRenderer.invoke('app:checkUpdates'),
  onPrint: on('menu:print'), onSearch: on('menu:search'), onNavigate: on('menu:navigate'),
  onReload: on('menu:reload'), onLogout: on('menu:logout'),
});
