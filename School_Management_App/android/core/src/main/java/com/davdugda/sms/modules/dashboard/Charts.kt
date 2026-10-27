package com.davdugda.sms.modules.dashboard

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextDecoration
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewModelScope
import com.davdugda.sms.core.data.*
import com.davdugda.sms.core.nav.*
import com.davdugda.sms.core.ui.*
import com.davdugda.sms.core.ui.components.*
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import kotlinx.serialization.json.*

import com.davdugda.sms.modules.dashboard.*
import com.davdugda.sms.modules.auth.*
import com.davdugda.sms.modules.profile.*
import com.davdugda.sms.modules.students.*
import com.davdugda.sms.modules.parents.*
import com.davdugda.sms.modules.teachers.*
import com.davdugda.sms.modules.staff.*
import com.davdugda.sms.modules.academics.*
import com.davdugda.sms.modules.attendance.*
import com.davdugda.sms.modules.timetable.*
import com.davdugda.sms.modules.homework.*
import com.davdugda.sms.modules.exams.*
import com.davdugda.sms.modules.fees.*
import com.davdugda.sms.modules.library.*
import com.davdugda.sms.modules.transport.*
import com.davdugda.sms.modules.hostel.*
import com.davdugda.sms.modules.admissions.*
import com.davdugda.sms.modules.certificates.*
import com.davdugda.sms.modules.accounts.*
import com.davdugda.sms.modules.payroll.*
import com.davdugda.sms.modules.inventory.*
import com.davdugda.sms.modules.front_office.*
import com.davdugda.sms.modules.communication.*
import com.davdugda.sms.modules.reports.*
import com.davdugda.sms.modules.settings.*
import com.davdugda.sms.modules.search.*
import com.davdugda.sms.modules.generic.*


import androidx.compose.foundation.Canvas
import androidx.compose.foundation.background
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.Path
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.graphics.drawscope.drawIntoCanvas
import androidx.compose.ui.graphics.nativeCanvas
import androidx.compose.ui.graphics.toArgb
import androidx.compose.ui.draw.clip

val ChartPalette = listOf(Color(0xFF3949AB), Color(0xFF43A047), Color(0xFFFB8C00), Color(0xFFE53935), Color(0xFF8E24AA), Color(0xFF00ACC1), Color(0xFFFFB300), Color(0xFF6D4C41), Color(0xFF546E7A), Color(0xFFD81B60))

fun parseColor(hex: String?, fallback: Color): Color = runCatching { Color(android.graphics.Color.parseColor(hex)) }.getOrDefault(fallback)

/** One chart card from dashboard/charts (bar, line, pie, doughnut), drawn with Compose Canvas. */
@Composable
fun ChartCard(chart: Chart, modifier: Modifier = Modifier) {
    ElevatedCard(modifier.fillMaxWidth(), colors = CardDefaults.elevatedCardColors(containerColor = MaterialTheme.colorScheme.surface)) {
        Column(Modifier.padding(14.dp)) {
            Text(chart.title, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
            Spacer(Modifier.height(10.dp))
            when (chart.type) {
                "pie", "doughnut" -> PieChart(chart, donut = chart.type == "doughnut")
                "line" -> BarLineChart(chart, line = true)
                else -> BarLineChart(chart, line = false)
            }
            if (chart.datasets.size > 1 && chart.type !in setOf("pie", "doughnut")) Legend(chart.datasets.mapIndexed { i, d -> (d.label ?: "Series ${i + 1}") to ChartPalette[i % ChartPalette.size] })
        }
    }
}

@Composable
private fun Legend(items: List<Pair<String, Color>>) {
    Column(Modifier.padding(top = 8.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
        items.chunked(2).forEach { row ->
            Row {
                row.forEach { (label, col) ->
                    Row(Modifier.weight(1f), verticalAlignment = Alignment.CenterVertically) {
                        Box(Modifier.size(10.dp).clip(CircleShape).background(col)); Spacer(Modifier.width(6.dp))
                        Text(label, style = MaterialTheme.typography.labelSmall, maxLines = 1, overflow = TextOverflow.Ellipsis)
                    }
                }
                if (row.size == 1) Spacer(Modifier.weight(1f))
            }
        }
    }
}

@Composable
private fun BarLineChart(chart: Chart, line: Boolean) {
    val values = chart.datasets.map { d -> d.data.map { it ?: 0.0 } }
    val max = (values.flatten().maxOrNull() ?: 0.0).let { if (it <= 0.0) 1.0 else it * 1.1 }
    val labelColor = MaterialTheme.colorScheme.onSurfaceVariant.toArgb()
    val grid = MaterialTheme.colorScheme.outlineVariant
    val n = chart.labels.size.coerceAtLeast(values.maxOfOrNull { it.size } ?: 0)
    if (n == 0) { Text("No data yet", color = MaterialTheme.colorScheme.outline); return }
    Canvas(Modifier.fillMaxWidth().height(170.dp)) {
        val bottom = size.height - 22.dp.toPx()
        val left = 34.dp.toPx()
        val w = size.width - left
        val text = android.graphics.Paint().apply { color = labelColor; textSize = 9.dp.toPx(); isAntiAlias = true }
        for (g in 0..4) {
            val y = bottom - bottom * g / 4f
            drawLine(grid, Offset(left, y), Offset(size.width, y), strokeWidth = 1f)
            drawIntoCanvas { it.nativeCanvas.drawText(compact(max * g / 4), 0f, y + 3.dp.toPx(), text) }
        }
        val slot = w / n
        values.forEachIndexed { si, series ->
            val col = ChartPalette[si % ChartPalette.size]
            if (line) {
                val path = Path()
                series.forEachIndexed { i, v ->
                    val x = left + slot * i + slot / 2; val y = bottom - (v / max * bottom).toFloat()
                    if (i == 0) path.moveTo(x, y) else path.lineTo(x, y)
                    drawCircle(col, 3.dp.toPx(), Offset(x, y))
                }
                drawPath(path, col, style = Stroke(width = 2.5.dp.toPx()))
            } else {
                val groupW = slot * 0.7f; val barW = groupW / values.size
                series.forEachIndexed { i, v ->
                    val h = (v / max * bottom).toFloat()
                    drawRoundRect(col, Offset(left + slot * i + (slot - groupW) / 2 + barW * si, bottom - h), Size(barW * 0.9f, h),
                        cornerRadius = androidx.compose.ui.geometry.CornerRadius(4.dp.toPx(), 4.dp.toPx()))
                }
            }
        }
        val every = ((n + 7) / 8).coerceAtLeast(1)
        chart.labels.forEachIndexed { i, l ->
            if (i % every != 0) return@forEachIndexed
            val t = if (l.length > 6) l.take(6) else l
            drawIntoCanvas { it.nativeCanvas.drawText(t, left + slot * i + slot / 2 - text.measureText(t) / 2, size.height - 4.dp.toPx(), text) }
        }
    }
}

private fun compact(v: Double): String = when {
    v >= 1e7 -> String.format("%.1fCr", v / 1e7)
    v >= 1e5 -> String.format("%.1fL", v / 1e5)
    v >= 1e3 -> String.format("%.0fk", v / 1e3)
    else -> String.format("%.0f", v)
}

@Composable
private fun PieChart(chart: Chart, donut: Boolean) {
    val data = chart.datasets.firstOrNull()?.data?.map { it ?: 0.0 }.orEmpty()
    val total = data.sum()
    if (total <= 0) { Text("No data yet", color = MaterialTheme.colorScheme.outline); return }
    Row(verticalAlignment = Alignment.CenterVertically) {
        Canvas(Modifier.size(130.dp)) {
            var start = -90f
            data.forEachIndexed { i, v ->
                val sweep = (v / total * 360).toFloat()
                if (donut) drawArc(ChartPalette[i % ChartPalette.size], start, sweep, false, style = Stroke(width = 26.dp.toPx()), topLeft = Offset(13.dp.toPx(), 13.dp.toPx()), size = Size(size.width - 26.dp.toPx(), size.height - 26.dp.toPx()))
                else drawArc(ChartPalette[i % ChartPalette.size], start, sweep, true)
                start += sweep
            }
        }
        Spacer(Modifier.width(14.dp))
        Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
            chart.labels.forEachIndexed { i, l ->
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Box(Modifier.size(10.dp).clip(CircleShape).background(ChartPalette[i % ChartPalette.size])); Spacer(Modifier.width(6.dp))
                    Text("$l  ${data.getOrNull(i)?.let { compact(it) } ?: ""}", style = MaterialTheme.typography.labelSmall)
                }
            }
        }
    }
}
