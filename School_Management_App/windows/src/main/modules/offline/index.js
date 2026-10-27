// Internet / server reachability. The renderer shows a friendly offline screen with Retry.
const { ipcMain, net } = require('electron');

function attach(win) {
  win.webContents.on('render-process-gone', () => { try { win.reload(); } catch (_) {} });
}

function registerIpc() {
  ipcMain.handle('net:status', async (_e, serverUrl) => {
    const online = net.isOnline();
    if (!serverUrl) return { online, server: false };
    try {
      const res = await net.fetch(serverUrl + 'ping', { headers: { Accept: 'application/json' } });
      return { online, server: res.ok };
    } catch (_) { return { online, server: false }; }
  });
}

module.exports = { attach, registerIpc };
