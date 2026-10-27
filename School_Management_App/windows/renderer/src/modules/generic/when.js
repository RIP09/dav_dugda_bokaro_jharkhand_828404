// Evaluates an action's `when` condition against a row, e.g. "status!=admitted", "status==issued&&amount>0", "paid=false".
export function evaluateWhen(expr, row) {
  if (!expr) return true;
  if (typeof expr === 'object') return Object.entries(expr).every(([k, v]) => String(row?.[k]) === String(v));
  return String(expr).split(/\s*(?:&&|,| and )\s*/).every(part => {
    const m = /^\s*([\w.]+)\s*(!=|==|=|>=|<=|>|<| in | not in )\s*(.*?)\s*$/.exec(part);
    if (!m) { // bare field name → truthy
      const k = part.trim(); if (!k) return true; if (k.startsWith('!')) return !row?.[k.slice(1)]; return !!row?.[k];
    }
    const [, key, opRaw, rawVal] = m; const op = opRaw.trim();
    const left = row?.[key];
    const vals = rawVal.replace(/^\[|\]$/g, '').split('|').map(s => s.trim().replace(/^['"]|['"]$/g, ''));
    const eq = (a, b) => {
      if (b === 'null') return a === null || a === undefined || a === '';
      if (b === 'true' || b === 'false') return Boolean(a === true || a === 1 || a === '1' || a === 'true') === (b === 'true');
      return String(a ?? '') === b;
    };
    switch (op) {
      case '=': case '==': return vals.some(v => eq(left, v));
      case '!=': return !vals.some(v => eq(left, v));
      case 'in': return vals.some(v => eq(left, v));
      case 'not in': return !vals.some(v => eq(left, v));
      case '>': return Number(left) > Number(vals[0]);
      case '<': return Number(left) < Number(vals[0]);
      case '>=': return Number(left) >= Number(vals[0]);
      case '<=': return Number(left) <= Number(vals[0]);
    }
    return true;
  });
}
