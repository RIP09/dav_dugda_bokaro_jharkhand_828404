package com.davdugda.sms.core.ui.screens

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
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import com.davdugda.sms.core.data.AppContainer
import com.davdugda.sms.core.data.Dashboard
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.ui.*
import com.davdugda.sms.core.ui.components.*

class DashboardViewModel(c: AppContainer) : BaseViewModel(c) {
    val state = loadState<Dashboard>()
    fun refresh(childId: Int?) = load(state, silent = true) { api.dashboard(childId).data!! }
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
    val vm = screenViewModel { DashboardViewModel(it) }
    val child = LocalChild.current.value
    val brand = MaterialTheme.colorScheme.primary
    LaunchedEffect(child) { vm.refresh(child) }
    LoadContent(vm.state.value, onRetry = { vm.refresh(child) }) { d ->
        LazyVerticalGrid(
            columns = GridCells.Adaptive(160.dp),
            contentPadding = PaddingValues(16.dp),
            horizontalArrangement = Arrangement.spacedBy(12.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            item(span = { GridItemSpan(maxLineSpan) }) {
                HeroHeader(d.greeting, d.date, brand) {
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
            item(span = { GridItemSpan(maxLineSpan) }) { SectionHeader("Quick access") }
            item(span = { GridItemSpan(maxLineSpan) }) {
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
private fun FeatureTile(f: Feature, modifier: Modifier, onClick: () -> Unit) {
    Column(modifier.clickable(onClick = onClick).padding(vertical = 4.dp), horizontalAlignment = Alignment.CenterHorizontally) {
        IconBadge(f.icon, f.tint, 52)
        Spacer(Modifier.height(6.dp))
        Text(f.title, style = MaterialTheme.typography.labelMedium, textAlign = TextAlign.Center, maxLines = 2, overflow = TextOverflow.Ellipsis)
    }
}
