// Global toasts and promise-based dialogs (confirm / prompt) used by every screen.
import { reactive } from 'vue';

export const ui = reactive({ toasts: [], dialog: null, paletteOpen: false });
let seq = 1;

export function toast(message, type = 'success', ms = 3200) {
  const id = seq++;
  ui.toasts.push({ id, message, type });
  setTimeout(() => { const i = ui.toasts.findIndex(t => t.id === id); if (i >= 0) ui.toasts.splice(i, 1); }, ms);
}
export const toastError = (e) => toast(e?.message || String(e), e?.status === 423 ? 'locked' : 'error', 5000);

export function confirmBox({ title = 'Are you sure?', message = '', ok = 'Yes', cancel = 'Cancel', danger = false } = {}) {
  return new Promise(resolve => { ui.dialog = { kind: 'confirm', title, message, ok, cancel, danger, resolve }; });
}
export function promptBox({ title, message = '', label = '', placeholder = '', required = false, ok = 'OK', multiline = true, value = '' } = {}) {
  return new Promise(resolve => { ui.dialog = { kind: 'prompt', title, message, label, placeholder, required, ok, multiline, value, resolve }; });
}
export function closeDialog(result) {
  const d = ui.dialog; ui.dialog = null; if (d) d.resolve(result);
}

/** A small form dialog: fields [{name,label,type:text|textarea|select|date|number,required,options,default,help}] → values or null. */
export function formBox({ title, message = '', fields = [], ok = 'OK', danger = false } = {}) {
  const values = Object.fromEntries(fields.map(f => [f.name, f.default ?? '']));
  return new Promise(resolve => { ui.dialog = { kind: 'form', title, message, fields, values, ok, danger, resolve }; });
}
