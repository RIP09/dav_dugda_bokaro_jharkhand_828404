// Cached list of sections (with their class) used by attendance, marks, timetable, fees screens.
import { get } from '../api/client';
let cache = null, at = 0;
export async function loadSections(force = false) {
  if (cache && !force && Date.now() - at < 5 * 60000) return cache;
  try { const r = await get('sections'); cache = r.data || []; at = Date.now(); } catch (_) { cache = cache || []; }
  return cache;
}
/** "6" / "VI" → "Class 6" / "Class VI"; "Class 6", "LKG", "Nursery" stay as they are (same rule as the server). */
export const classDisplayName = (n) => { const s = String(n ?? '').trim(); return /^(\d{1,2}|[IVX]{1,4}|[ivx]{1,4})(st|nd|rd|th)?$/.test(s) ? 'Class ' + s : s; };
/** Always says which class: "Class 6 – A", "Class 11 – A (Science)". Uses the server's label when it sends one (v2.0.1+). */
export const sectionLabel = (s) => {
  if (!s) return '';
  if (s.label) return s.label;
  const c = classDisplayName(s.school_class?.name ?? s.class_name ?? '');
  return (c ? `${c} – ${s.name}` : `Section ${s.name}`) + (s.stream ? ` (${s.stream})` : '');
};
