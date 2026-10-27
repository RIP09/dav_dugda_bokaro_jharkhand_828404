// Formatting helpers (Indian locale: ₹, dd Mon yyyy).
export const roleLabels = { admin: 'Principal / Admin', teacher: 'Teacher', accountant: 'Accountant', librarian: 'Librarian', staff: 'Staff', parent: 'Parent', student: 'Student' };

const inr = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 2 });
export const money = (v) => (v === null || v === undefined || v === '' ? '—' : inr.format(Number(v)).replace(/\.00$/, ''));
export const num = (v) => (v === null || v === undefined || v === '' ? '—' : Number(v).toLocaleString('en-IN'));

export function date(v) {
  if (!v) return '—';
  const d = new Date(String(v).length === 10 ? v + 'T00:00:00' : v);
  if (isNaN(d)) return String(v);
  return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
}
export function datetime(v) {
  if (!v) return '—';
  const d = new Date(v); if (isNaN(d)) return String(v);
  return d.toLocaleString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
export function ago(v) {
  if (!v) return '';
  const s = (Date.now() - new Date(v).getTime()) / 1000;
  if (s < 60) return 'just now'; if (s < 3600) return Math.floor(s / 60) + ' min ago'; if (s < 86400) return Math.floor(s / 3600) + ' h ago';
  if (s < 86400 * 7) return Math.floor(s / 86400) + ' d ago';
  return date(v);
}
export const today = () => new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10);
export const thisMonth = () => today().slice(0, 7);
export const humanize = (k) => String(k || '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
