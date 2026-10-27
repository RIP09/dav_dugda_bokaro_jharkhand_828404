<template><div class="chart"><canvas ref="cv" /></div></template>
<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import Chart from 'chart.js/auto';
const props = defineProps({ chart: Object, height: { type: Number, default: 260 } });
const cv = ref(null); let inst = null;
const palette = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#a855f7', '#ec4899', '#84cc16', '#f97316', '#14b8a6'];
function build() {
  if (inst) inst.destroy();
  const c = props.chart || {}; const type = c.type || 'bar'; const round = type === 'pie' || type === 'doughnut';
  const datasets = (c.datasets || []).map((d, i) => ({
    ...d,
    backgroundColor: d.backgroundColor || (round ? palette : (type === 'line' ? palette[i % 10] + '22' : palette[i % 10])),
    borderColor: d.borderColor || (round ? '#fff' : palette[i % 10]),
    borderWidth: round ? 2 : (type === 'line' ? 2.5 : 0), borderRadius: type === 'bar' ? 6 : 0, tension: .35, fill: type === 'line', pointRadius: 3, maxBarThickness: 38,
  }));
  inst = new Chart(cv.value, {
    type, data: { labels: c.labels || [], datasets },
    options: { responsive: true, maintainAspectRatio: false, animation: { duration: 900, easing: 'easeOutQuart' },
      plugins: { legend: { display: round || datasets.length > 1, position: round ? 'right' : 'top', labels: { usePointStyle: true, boxWidth: 8, font: { family: 'Inter Variable' } } } },
      scales: round ? {} : { x: { grid: { display: false }, ticks: { font: { size: 11 } } }, y: { beginAtZero: true, grid: { color: '#eef1f6' }, ticks: { font: { size: 11 } } } },
      cutout: type === 'doughnut' ? '62%' : undefined },
  });
}
onMounted(build); watch(() => props.chart, build, { deep: true });
onBeforeUnmount(() => inst && inst.destroy());
</script>
<style scoped>.chart { position: relative; height: v-bind(height + 'px'); }</style>
