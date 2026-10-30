// Helpers around the server's module registry (GET meta/modules).
import { app } from '../../state';
import { get } from '../../api/client';
import { humanize } from '../../ui/format';

export function moduleMeta(key) { return app.modules[key] || null; }

/** Loads a module's meta even if it is not in the signed-in user's list (e.g. a ref target). */
export async function ensureMeta(key) {
  if (app.modules[key]) return app.modules[key];
  try { const r = await get('meta/modules/' + key); if (r?.data) { app.modules[key] = r.data; return r.data; } } catch (_) {}
  return null;
}

export const listFields = (m) => {
  const f = (m?.fields || []).filter(x => x.list);
  return f.length ? f : (m?.fields || []).slice(0, 5);
};
export const filterFields = (m) => (m?.fields || []).filter(x => x.filter);
export const formFields = (m, editing) => (m?.fields || []).filter(x => !x.readonly && x.type !== 'json' && !(x.hidden));
export const detailFields = (m) => (m?.fields || []).filter(x => !x.hidden);

export function rowTitle(m, row) {
  if (!row) return '';
  return row._title || row[m?.label_field] || row.name || row.title || ('#' + row.id);
}
export function rowSubtitle(m, row) {
  if (!row) return '';
  if (row._subtitle) return row._subtitle;
  return (m?.subtitle_fields || []).map(f => row[f + '_label'] ?? row[f]).filter(v => v !== null && v !== undefined && v !== '').join(' · ');
}
export const fieldLabel = (f) => f.label || humanize(f.name);

/**
 * The picker a ref field waits on (Section ← Class, Subject ← Class, Student ← Section). The server says so in
 * ref.depends_on (v2.0.1+); for older servers the same rule is worked out from the sibling fields.
 */
export function dependencyOf(f, fields) {
  if (f?.type !== 'ref' || !f.ref) return null;
  if (f.ref.depends_on) return f.ref.depends_on;
  const names = new Set((fields || []).map(x => x.name));
  if (['sections', 'subjects'].includes(f.ref.module)) {
    const p = f.name.replace(/(section|subject)_id$/, 'class_id');
    return p !== f.name && names.has(p) ? { field: p, key: 'class_id' } : null;
  }
  if (f.ref.module === 'students') {
    const pre = f.name.replace(/student_id$/, '');
    if (names.has(pre + 'section_id')) return { field: pre + 'section_id', key: 'section_id' };
    if (names.has(pre + 'class_id')) return { field: pre + 'class_id', key: 'class_id' };
  }
  return null;
}
export const can = (m, p) => !!m?.permissions?.[p];
