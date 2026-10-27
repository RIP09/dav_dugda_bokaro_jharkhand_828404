<template>
  <div class="stat" :style="{ '--c': color }">
    <div class="ico"><Icon :name="icon || 'insights'" /></div>
    <div class="txt"><div class="lbl">{{ label }}</div><div class="val">{{ shown }}</div><div v-if="hint" class="hint">{{ hint }}</div></div>
  </div>
</template>
<script setup>
import { ref, onMounted, watch } from 'vue';
import Icon from './Icon.vue';
const props = defineProps({ label: String, value: [String, Number], icon: String, color: { type: String, default: '#6366f1' }, hint: String });
const shown = ref('');
// Animated counter: counts up the numeric part, keeps ₹, %, commas and suffix text.
function animate() {
  const raw = String(props.value ?? '—'); const m = /^([^\d-]*)(-?[\d,]+(?:\.\d+)?)(.*)$/.exec(raw);
  if (!m) { shown.value = raw; return; }
  const [, pre, numStr, post] = m; const target = parseFloat(numStr.replace(/,/g, '')); const dec = (numStr.split('.')[1] || '').length;
  const t0 = performance.now(), dur = 900;
  const step = (t) => { const k = Math.min(1, (t - t0) / dur); const e = 1 - Math.pow(1 - k, 3);
    shown.value = pre + (target * e).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec }) + post; if (k < 1) requestAnimationFrame(step); };
  requestAnimationFrame(step);
}
onMounted(animate); watch(() => props.value, animate);
</script>
<style scoped>
.stat { display: flex; gap: 14px; align-items: center; padding: 18px; background: #fff; border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); transition: transform .2s var(--ease), box-shadow .2s; position: relative; overflow: hidden; }
.stat::after { content: ''; position: absolute; right: -30px; top: -30px; width: 100px; height: 100px; border-radius: 50%; background: var(--c); opacity: .07; }
.stat:hover { transform: translateY(-3px); box-shadow: 0 14px 30px -12px rgba(15,23,42,.2); }
.ico { width: 48px; height: 48px; border-radius: 14px; display: grid; place-items: center; color: #fff; background: var(--c); box-shadow: 0 8px 18px -8px var(--c); flex: none; }
.lbl { color: var(--muted); font-size: 12.5px; font-weight: 550; }
.val { font-size: 24px; font-weight: 750; letter-spacing: -.02em; margin-top: 2px; font-variant-numeric: tabular-nums; }
.hint { font-size: 12px; color: var(--faint); }
</style>
