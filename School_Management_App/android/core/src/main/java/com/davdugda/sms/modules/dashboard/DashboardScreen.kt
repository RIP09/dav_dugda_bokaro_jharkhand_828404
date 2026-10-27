package com.davdugda.sms.modules.dashboard

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.GridItemSpan
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.testTag
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import com.davdugda.sms.core.data.AppContainer
import com.davdugda.sms.core.data.Dashboard
import com.davdugda.sms.core.nav.*
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.launch
import com.davdugda.sms.core.ui.*
import com.davdugda.sms.core.ui.components.*
import com.davdugda.sms.core.data.*
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

class DashboardViewModel(c: AppContainer) : BaseViewModel(c) {
    val state = loadState<Dashboard>()
    var charts by mutableStateOf<DashboardCharts?>(null)
    var menu by mutableStateOf<List<MenuGroup>>(emptyList())
    var principal by mutableStateOf<String?>(null)
    fun refresh(childId: Int?) {
        load(state, silent = true) { api.dashboard(childId).data!! }
        viewModelScope.launch {
            runCatching { c.meta.load() }
            menu = c.meta.menu; principal = c.meta.principalName
            charts = runCatching {
                val o = api.dashboardCharts()
                c.json.decodeFromJsonElement(DashboardCharts.serializer(), (o["data"] as? kotlinx.serialization.json.JsonObject) ?: o)
            }.getOrNull()
        }
    }
}

private fun statIcon(key: String?): Pair<ImageVector, Color> = when (key) {
    "students" -> Icons.Outlined.School to Color(0xFF3949AB)
    "staff" -> Icons.Outlined.Badge to Color(0xFF00897B)
    "parents" -> Icons.Outlined.FamilyRestroom to Color(0xFFD81B60)
    "attendance" -> Icons.Outlined.FactCheck to Color(0xFF43A047)
    "fees" -> Icons.Outlined.CurrencyRupee to Color(0xFF2E7D32)
    "due" -> Icons.Outlined.ReportProblem to Color(0xFFE53935)
    "timetable" -> Icons.Outlined.Schedule to Color(0xFF1E88E5)
    "homework" -> Icons.Outlined.MenuBook to Color(0xFF8E24AA)
    "leave" -> Icons.Outlined.BeachAccess to Color(0xFF039BE5)
    "library" -> Icons.Outlined.LocalLibrary to Color(0xFF6D4C41)
    else -> Icons.Outlined.Today to Color(0xFFFB8C00)
}

@Composable
fun DashboardScreen(features: List<Feature>, onOpen: (String) -> Unit) {
    val c = LocalContainer.current
    val user = LocalUser.current
    val vm = screenViewModel { DashboardViewModel(it) }
    val child = LocalChild.current.value
    val brand = MaterialTheme.colorScheme.primary
    LaunchedEffect(child) { vm.refresh(child) }
    LoadContent(vm.state.value, onRetry = { vm.refresh(child) }) { d ->
        LazyVerticalGrid(
            modifier = Modifier.testTag("dashboard_grid"),
            columns = GridCells.Adaptive(160.dp),
            contentPadding = PaddingValues(16.dp),
            horizontalArrangement = Arrangement.spacedBy(12.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            item(span = { GridItemSpan(maxLineSpan) }) {
                HeroHeader(d.greeting, d.date, brand) {
                    val p = vm.principal
                    if (p != null && c.config.appRole == "school") {
                        Spacer(Modifier.height(6.dp))
                        Text("Principal: $p", color = Color.White, style = MaterialTheme.typography.labelLarge)
                    }
                    val kid = d.children.firstOrNull { it.id == child } ?: d.children.firstOrNull()
                    if (kid != null) {
                        Spacer(Modifier.height(8.dp))
                        Text("${kid.displayName} · ${kid.classLabel ?: ""} · Roll ${kid.rollNo ?: "-"}", color = Color.White.copy(alpha = 0.9f))
                    }
                    if (d.unreadMessages > 0) {
                        Spacer(Modifier.height(10.dp))
                        AssistChip(onClick = { onOpen(Feature.Messages.route) }, label = { Text("${d.unreadMessages} unread message(s)") },
                            colors = AssistChipDefaults.assistChipColors(containerColor = Color.White.copy(alpha = 0.2f), labelColor = Color.White))
                    }
                }
            }
            items(d.stats) { s ->
                val (icon, color) = statIcon(s.icon)
                StatCard(s.label, s.value, icon, color)
            }
            if (d.alerts.isNotEmpty()) item(span = { GridItemSpan(maxLineSpan) }) {
                Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.secondaryContainer)) {
                    Column(Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                        d.alerts.forEach { a ->
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Icon(Icons.Outlined.NotificationsActive, null, Modifier.size(18.dp), tint = MaterialTheme.colorScheme.secondary)
                                Spacer(Modifier.width(8.dp)); Text(a, style = MaterialTheme.typography.bodyMedium)
                            }
                        }
                    }
                }
            }
            val ch = vm.charts
            if (ch != null && d.stats.isEmpty() && ch.cards.isNotEmpty()) items(ch.cards) { k ->
                StatCard(k.label, k.value.asText(), iconFor(k.icon), parseColor(k.color, MaterialTheme.colorScheme.primary))
            }
            if (ch != null && ch.charts.isNotEmpty()) {
                item(span = { GridItemSpan(maxLineSpan) }) { SectionHeader("At a glance") }
                items(ch.charts, span = { GridItemSpan(maxLineSpan) }) { ChartCard(it) }
            }
            item(span = { GridItemSpan(maxLineSpan) }) { SectionHeader("Quick access") }
            val menuGroups = vm.menu.filter { g -> g.items.any { it.screen !in setOf("dashboard", "notifications", "search") } }
            if (menuGroups.isNotEmpty()) item(span = { GridItemSpan(maxLineSpan) }) {
                val cols = 4
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    menuGroups.forEach { g ->
                        val tiles = menuEntries(g.items.filter { it.screen != "dashboard" && it.screen != "notifications" && it.screen != "search" }, c.config.appRole, c.meta)
                        if (tiles.isEmpty()) return@forEach
                        if (g.group.isNotBlank()) Text(g.group, style = MaterialTheme.typography.labelLarge, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        tiles.chunked(cols).forEach { row ->
                            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                row.forEach { (m, r) -> Tile(m.title, iconFor(m.icon, m.screen), tintFor(m.screen), Modifier.weight(1f)) { onOpen(r) } }
                                repeat(cols - row.size) { Spacer(Modifier.weight(1f)) }
                            }
                        }
                    }
                }
            } else item(span = { GridItemSpan(maxLineSpan) }) {
                val cols = 4
                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    features.chunked(cols).forEach { row ->
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            row.forEach { f -> FeatureTile(f, Modifier.weight(1f)) { onOpen(f.route) } }
                            repeat(cols - row.size) { Spacer(Modifier.weight(1f)) }
                        }
                    }
                }
            }
            if (d.todayClasses.isNotEmpty()) {
                item(span = { GridItemSpan(maxLineSpan) }) { SectionHeader("Today's classes") }
                items(d.todayClasses, span = { GridItemSpan(maxLineSpan) }) { e ->
                    InfoCard(e.subjectName ?: "Period", subtitle = "${e.startTime} – ${e.endTime} · ${e.classLabel ?: ""}",
                        supporting = listOfNotNull(e.teacherName, e.room?.let { "Room $it" }).joinToString(" · "),
                        leading = { IconBadge(Icons.Outlined.Schedule, Color(0xFF1E88E5)) })
                }
            }
            if (d.notices.isNotEmpty()) {
                item(span = { GridItemSpan(maxLineSpan) }) { SectionHeader("Latest notices") { TextButton(onClick = { onOpen(Feature.Notices.route) }) { Text("All") } } }
                items(d.notices, span = { GridItemSpan(maxLineSpan) }) { n ->
                    InfoCard(n.title, subtitle = prettyDate(n.publishDate), supporting = n.body,
                        leading = { IconBadge(if (n.isPinned) Icons.Outlined.PushPin else Icons.Outlined.Campaign, Color(0xFFE53935)) },
                        onClick = { onOpen(Feature.Notices.route) })
                }
            }
            if (d.events.isNotEmpty()) {
                item(span = { GridItemSpan(maxLineSpan) }) { SectionHeader("Coming up") }
                items(d.events, span = { GridItemSpan(maxLineSpan) }) { e ->
                    InfoCard(e.title, subtitle = prettyDate(e.startDate) + (e.endDate?.let { " – " + prettyDate(it) } ?: ""), supporting = e.location,
                        leading = { IconBadge(Icons.Outlined.Celebration, Color(0xFFFB8C00)) })
                }
            }
            item(span = { GridItemSpan(maxLineSpan) }) { Spacer(Modifier.height(24.dp)) }
        }
    }
}

@Composable
private fun FeatureTile(f: Feature, modifier: Modifier, onClick: () -> Unit) = Tile(f.title, f.icon, f.tint, modifier, onClick)

@Composable
fun Tile(title: String, icon: ImageVector, tint: Color, modifier: Modifier, onClick: () -> Unit) {
    Column(modifier.clickable(onClick = onClick).padding(vertical = 4.dp), horizontalAlignment = Alignment.CenterHorizontally) {
        IconBadge(icon, tint, 52)
        Spacer(Modifier.height(6.dp))
        Text(title, style = MaterialTheme.typography.labelMedium, textAlign = TextAlign.Center, maxLines = 2, overflow = TextOverflow.Ellipsis)
    }
}
