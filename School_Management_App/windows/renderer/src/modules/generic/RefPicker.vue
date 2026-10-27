<template>
  <div class="ref" v-click-away="close">
    <button type="button" class="input trigger" :class="{ invalid, sm }" :disabled="disabled" @click="toggle">
      <span v-if="modelValue !== null && modelValue !== undefined && modelValue !== ''" class="val">{{ currentLabel }}</span>
      <span v-else class="faint">{{ placeholder || 'Choose…' }}</span>
      <span class="grow" />
      <span v-if="clearable && modelValue && !disabled" class="material-icons x" @click.stop="pick(null)">close</span>
      <span class="material-icons">unfold_more</span>
    </button>
    <transition name="pop">
      <div v-if="open" class="menu">
        <input ref="inp" v-model="q" class="input sm" placeholder="Type to search…" @keydown.down.prevent="hi = Math.min(hi + 1, items.length - 1)" @keydown.up.prevent="hi = Math.max(hi - 1, 0)" @keydown.enter.prevent="items[hi] && pick(items[hi])" @keydown.esc="close" />
        <div class="list">
          <div v-if="loading" class="muted p">Loading…</div>
          <div v-else-if="error" class="err p">{{ error }}</div>
          <div v-for="(it, i) in items" :key="it.id" class="opt" :class="{ hi: i === hi, sel: String(it.id) === String(modelValue) }" @mouseenter="hi = i" @click="pick(it)">
            <div>{{ it._label }}</div><div v-if="it._sub" class="small muted">{{ it._sub }}</div>
          </div>
          <div v-if="!loading && !error && !items.length" class="muted p">Nothing found</div>
        </div>
      </div>
    </transition>
  </div>
</template>
<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { get } from '../../api/client';
import { app } from '../../state';
const props = defineProps({ modelValue: [String, Number, null], refInfo: Object, label: String, placeholder: String, invalid: Boolean, disabled: Boolean, clearable: { type: Boolean, default: true }, sm: Boolean, params: Object });
const emit = defineEmits(['update:modelValue', 'picked']);
const open = ref(false), q = ref(''), rows = ref([]), loading = ref(false), error = ref(''), hi = ref(0), inp = ref(null), picked = ref(null);
const vClickAway = { mounted(el, b) { el._ca = (e) => { if (!el.contains(e.target)) b.value(); }; document.addEventListener('mousedown', el._ca); }, unmounted(el) { document.removeEventListener('mousedown', el._ca); } };
const labelField = computed(() => props.refInfo?.label_field || app.modules[props.refInfo?.module]?.label_field || 'name');
const toItem = (r) => ({ ...r, _label: r._title || r[labelField.value] || r.name || r.title || ('#' + r.id), _sub: r._subtitle || '' });
const items = computed(() => rows.value.map(toItem));
const currentLabel = computed(() => picked.value?._label || props.label || items.value.find(i => String(i.id) === String(props.modelValue))?._label || ('#' + props.modelValue));
let t, seq = 0;
async function load() {
  if (!props.refInfo?.module) return;
  const my = ++seq; loading.value = true; error.value = '';
  try {
    const r = await get('records/' + props.refInfo.module, { q: q.value || undefined, per_page: 30, ...(props.params || {}), ...(props.refInfo.params || {}) });
    if (my === seq) rows.value = r.data || [];
  } catch (e) { if (my === seq) { error.value = e.message; rows.value = []; } }
  if (my === seq) { loading.value = false; hi.value = 0; }
}
watch(q, () => { clearTimeout(t); t = setTimeout(load, 220); });
watch(() => props.params, () => { rows.value = []; }, { deep: true });
function toggle() { open.value = !open.value; if (open.value) { if (!rows.value.length) load(); nextTick(() => inp.value?.focus()); } }
function close() { open.value = false; }
function pick(it) { picked.value = it; emit('update:modelValue', it ? it.id : null); emit('picked', it); close(); }
</script>
<style scoped>
.ref { position: relative; }
.trigger { display: flex; align-items: center; gap: 6px; text-align: left; cursor: pointer; }
.trigger .material-icons { font-size: 18px; color: var(--faint); }
.trigger .x:hover { color: var(--danger); }
.val { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.menu { position: absolute; left: 0; right: 0; top: calc(100% + 4px); min-width: 260px; background: #fff; border: 1px solid var(--border); border-radius: 12px; box-shadow: var(--shadow-lg); z-index: 600; padding: 8px; }
.list { max-height: 260px; overflow: auto; margin-top: 6px; }
.opt { padding: 8px 10px; border-radius: 8px; cursor: pointer; font-size: 13.5px; }
.opt.hi { background: var(--primary-50); } .opt.sel { font-weight: 650; color: var(--primary); }
.p { padding: 10px; font-size: 13px; } .err { color: var(--danger); }
</style>
