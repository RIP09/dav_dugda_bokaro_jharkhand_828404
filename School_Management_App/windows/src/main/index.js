// DAV School Management - Electron main process entry.
// Each desktop feature lives in its own folder under src/main/modules/.
const { app, BrowserWindow } = require('electron');
const config = require('./modules/server-config');
const windowModule = require('./modules/window');
const menu = require('./modules/menu');
const downloads = require('./modules/downloads');
const printing = require('./modules/printing');
const offline = require('./modules/offline');
const about = require('./modules/about');
const updates = require('./modules/updates');

app.setAppUserModelId('in.davdugda.sms.desktop');

// Single instance: a second launch focuses the running window.
if (!app.requestSingleInstanceLock()) {
  app.quit();
} else {
  app.on('second-instance', () => windowModule.focus());

  app.whenReady().then(() => {
    config.init();
    config.registerIpc();
    downloads.registerIpc();
    printing.registerIpc();
    offline.registerIpc();
    about.registerIpc();
    updates.registerIpc();
    const win = windowModule.create();
    menu.build(win);
    app.on('activate', () => { if (BrowserWindow.getAllWindows().length === 0) windowModule.create(); });
  });

  app.on('window-all-closed', () => { if (process.platform !== 'darwin') app.quit(); });
}
