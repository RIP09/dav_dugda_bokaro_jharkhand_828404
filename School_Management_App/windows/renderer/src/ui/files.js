// Opening / saving / printing server files (PDF, Excel, CSV, backup zips).
import { fileUrl, desktop, isDesktop } from '../api/client';
import { toast, toastError } from './ui';

export async function downloadFile(path, query = {}, { filename = 'download', saveAs = false, open = true } = {}) {
  const url = fileUrl(path, query);
  if (!isDesktop) { window.open(url, '_blank'); return { ok: true }; }
  toast('Downloading ' + filename + '…', 'info', 1800);
  const r = await desktop.download({ url, filename, saveAs, open });
  if (r.ok) toast('Saved to ' + r.path, 'success', 4500);
  else if (!r.canceled) toastError({ message: r.error || 'Download failed', status: r.status });
  return r;
}

export function openPdf(doc, id, query = {}, title) {
  const path = id != null ? `pdf/${doc}/${id}` : `pdf/${doc}`;
  return downloadFile(path, query, { filename: `${title || doc}${id != null ? '_' + id : ''}.pdf` });
}

export async function printPdf(doc, id, query = {}, title) {
  const path = id != null ? `pdf/${doc}/${id}` : `pdf/${doc}`;
  const url = fileUrl(path, query);
  if (isDesktop) return desktop.printPdf(url, title || 'Print');
  window.open(url, '_blank');
}

export function printPage() { return isDesktop ? desktop.printPage() : window.print(); }
export function openExternal(url) { return isDesktop ? desktop.openExternal(url) : window.open(url, '_blank'); }
