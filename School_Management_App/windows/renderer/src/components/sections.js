// Cached list of sections (with their class) used by attendance, marks, timetable, fees screens.
import { get } from '../api/client';
let cache = null, at = 0;
export async function loadSections(force = false) {
  if (cache && !force && Date.now() - at < 5 * 60000) return cache;
  try { const r = await get('sections'); cache = r.data || []; at = Date.now(); } catch (_) { cache = cache || []; }
  return cache;
}
export const sectionLabel = (s) => s ? `${s.school_class?.name ?? s.class_name ?? ''} - ${s.name}` : '';
