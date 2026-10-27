// Runs a module action from meta/modules:
//   kind "pdf"  → downloads/opens the file at action.url (e.g. "pdf/receipt/{id}")
//   kind "post" → confirm, asks a reason when needs_reason, asks extra `inputs` when given, then POSTs.
import { post } from '../../api/client';
import { confirmBox, formBox, toast, toastError } from '../../ui/ui';
import { downloadFile, openPdf } from '../../ui/files';
import { extensions } from '../registry';

const REASON_ACTIONS = ['cancel', 'reject', 'reverse', 'void', 'refund'];
const DEFAULT_DOCS = { payments: 'receipt', invoices: 'invoice', certificates: 'certificate', payslips: 'salary-slip', students: 'id-card' };

export function needsReason(action) {
  if (action.needs_reason !== undefined) return !!action.needs_reason;
  return !!(action.reason || action.requires_reason || REASON_ACTIONS.includes(action.key));
}

function fill(url, row) { return String(url).replace(/\{(\w+)\}/g, (_, k) => row?.[k] ?? ''); }

export async function runAction(meta, row, action) {
  if (action.kind === 'pdf' || action.kind === 'print' || action.kind === 'download') {
    const title = String(row?.receipt_no || row?.certificate_no || row?.invoice_no || row?.admission_no || row?._title || action.key).replace(/[\s/]+/g, '_');
    if (action.url) {
      const [p, qs] = fill(action.url, row).replace(/^\/?(api\/v1\/)?/, '').split('?');
      return downloadFile(p, Object.fromEntries(new URLSearchParams(qs || '')), { filename: `${action.label.replace(/\s*\(PDF\)/i, '').replace(/\W+/g, '_')}_${title}.pdf` });
    }
    const doc = action.doc || extensions[meta.key]?.pdfDoc || DEFAULT_DOCS[meta.key] || meta.key;
    return openPdf(doc, row.id, action.query || {}, title);
  }
  const reason = needsReason(action);
  const inputs = action.inputs || [];
  let body = {};
  if (reason || inputs.length) {
    const fields = [...inputs];
    if (reason) fields.push({ name: 'reason', label: 'Reason', type: 'textarea', required: true, help: 'Saved permanently in the history.' });
    const r = await formBox({ title: action.label, message: action.confirm || '', fields, ok: action.label, danger: /cancel|reject|lock/.test(action.key) });
    if (!r) return null;
    body = r;
  } else if (action.confirm) {
    const ok = await confirmBox({ title: action.label, message: action.confirm, ok: action.label, danger: /delete|cancel|lock/i.test(action.key) });
    if (!ok) return null;
  }
  try {
    const path = action.url ? fill(action.url, row).replace(/^\/?(api\/v1\/)?/, '') : `records/${meta.key}/${row.id}/actions/${action.key}`;
    const r = await post(path, body);
    toast(r?.message || (action.label + ' done'));
    return r;
  } catch (e) { toastError(e); return null; }
}
