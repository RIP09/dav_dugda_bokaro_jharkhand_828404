<template>
  <span v-if="isEmpty" class="faint">—</span>
  <img v-else-if="field.type === 'image'" :src="url" class="thumb" :class="{ big }" alt="" @click.stop="big && open(url)" />
  <a v-else-if="field.type === 'file'" href="#" @click.prevent.stop="open(url)"><Icon name="attach_file" :size="16" />{{ fileName }}</a>
  <span v-else-if="field.type === 'bool'" class="badge" :class="truthy ? 'green' : ''">{{ truthy ? 'Yes' : 'No' }}</span>
  <span v-else-if="field.type === 'select' && statusColor" class="badge" :class="statusColor">{{ text }}</span>
  <a v-else-if="field.type === 'phone'" :href="'tel:' + value" @click.stop>{{ value }}</a>
  <a v-else-if="field.type === 'email'" :href="'mailto:' + value" @click.stop>{{ value }}</a>
  <span v-else-if="field.type === 'money'" class="money">{{ text }}</span>
  <pre v-else-if="field.type === 'json'" class="json">{{ JSON.stringify(value, null, 2) }}</pre>
  <span v-else :class="{ clamp: field.type === 'textarea' && !big }">{{ text }}</span>
</template>
<script setup>
import { computed } from 'vue';
import Icon from '../../components/Icon.vue';
import { money, date, datetime, num } from '../../ui/format';
import { openExternal } from '../../ui/files';
const props = defineProps({ field: Object, row: Object, big: Boolean });
const value = computed(() => props.row?.[props.field.name]);
const url = computed(() => props.row?.[props.field.name + '_url'] || (typeof value.value === 'string' && /^https?:/.test(value.value) ? value.value : null));
const truthy = computed(() => value.value === true || value.value === 1 || value.value === '1' || value.value === 'true');
const isEmpty = computed(() => {
  if (props.field.type === 'bool') return false;
  if (props.field.type === 'ref') return (props.row?.[props.field.name + '_label'] ?? value.value) == null || value.value === '';
  if (props.field.type === 'image' || props.field.type === 'file') return !url.value && !value.value;
  return value.value === null || value.value === undefined || value.value === '';
});
const fileName = computed(() => String(value.value || url.value || '').split('/').pop());
const text = computed(() => {
  const f = props.field, v = value.value;
  switch (f.type) {
    case 'ref': return props.row?.[f.name + '_label'] ?? ('#' + v);
    case 'money': return money(v);
    case 'number': return num(v);
    case 'date': return date(v);
    case 'datetime': return datetime(v);
    case 'select': return (f.options || []).find(o => String(o.value) === String(v))?.label ?? v;
    default: return v;
  }
});
const statusColor = computed(() => {
  const v = String(value.value || '').toLowerCase();
  if (!/status|state|result|type|priority/.test(props.field.name)) return '';
  if (/^(paid|active|present|issued|approved|admitted|resolved|closed|returned|published|completed|done|pass|in_stock|confirmed)$/.test(v)) return 'green';
  if (/^(cancelled|canceled|rejected|absent|overdue|lost|inactive|failed|fail|left|unpaid|high|urgent)$/.test(v)) return 'red';
  if (/^(pending|draft|partial|new|open|late|in_progress|issued_out|half_day|leave|follow_up|medium|waiting)$/.test(v)) return 'amber';
  return 'indigo';
});
function open(u) { if (u) openExternal(u); }
</script>
<style scoped>
.thumb { width: 34px; height: 34px; border-radius: 9px; object-fit: cover; background: #f1f5f9; vertical-align: middle; }
.thumb.big { width: 120px; height: 120px; border-radius: 16px; cursor: zoom-in; }
.clamp { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; max-width: 380px; }
.money { font-variant-numeric: tabular-nums; font-weight: 550; }
.json { margin: 0; font-size: 12px; background: var(--panel-2); padding: 8px; border-radius: 8px; max-height: 200px; overflow: auto; }
</style>
