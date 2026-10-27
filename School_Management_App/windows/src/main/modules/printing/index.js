// Printing: the current page (Ctrl+P) or a PDF (receipts, report cards, certificates) in a print preview window.
const { ipcMain, BrowserWindow } = require('electron');
const path = require('path');

function registerIpc() {
  ipcMain.handle('print:page', (e) => new Promise(resolve => {
    const wc = e.sender;
    wc.print({ printBackground: true }, (ok, reason) => resolve({ ok, reason }));
  }));

  // Opens the PDF in Chromium's built-in viewer (which has Print + Download buttons).
  ipcMain.handle('print:pdf', (e, { url, title }) => {
    const parent = BrowserWindow.fromWebContents(e.sender);
    const w = new BrowserWindow({
      width: 980, height: 900, parent, title: title || 'Print preview', autoHideMenuBar: true,
      icon: require('../window').iconPath(),
      webPreferences: { plugins: true, contextIsolation: true, sandbox: true },
    });
    w.loadURL(url);
    return { ok: true };
  });
}

module.exports = { registerIpc };
