// Help → About.
const { dialog, app, ipcMain, BrowserWindow } = require('electron');

function show(win) {
  return dialog.showMessageBox(win, {
    type: 'info', title: 'About',
    message: 'DAV School Management ' + app.getVersion(),
    detail: 'DAV Public School, Dugda\nBokaro, Jharkhand 828404\n\nPrincipal: Prasanjeet Kumar Paul\n\n' +
      'Windows management software for the principal, office, accountant, librarian and teachers.\n' +
      'Built with free & open-source software (Electron, Vue, Chart.js).\n\nElectron ' + process.versions.electron + ' · Chrome ' + process.versions.chrome,
    buttons: ['OK'],
  });
}

function registerIpc() {
  ipcMain.handle('app:about', (e) => show(BrowserWindow.fromWebContents(e.sender)));
}

module.exports = { show, registerIpc };
