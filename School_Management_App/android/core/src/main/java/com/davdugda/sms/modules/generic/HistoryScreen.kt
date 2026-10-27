package com.davdugda.sms.modules.generic

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


/** Who changed what and when, for one record (admin). */
class HistoryViewModel(c: AppContainer, val key: String, val id: String) : BaseViewModel(c) {
    val state = loadState<List<AuditRow>>()
    fun refresh() = load(state) { api.recordHistory(key, id).data }
}

@Composable
fun RecordHistoryScreen(key: String, id: String) {
    val vm = screenViewModel(key = "hist_${key}_$id") { HistoryViewModel(it, key, id) }
    LaunchedEffect(Unit) { vm.refresh() }
    LoadContent(vm.state.value, onRetry = { vm.refresh() }) { rows ->
        ScreenList {
            item { Text("Every change to this record, newest first.", color = MaterialTheme.colorScheme.onSurfaceVariant) }
            if (rows.isEmpty()) item { EmptyState("No changes recorded yet.", Icons.Outlined.History) }
            items(rows) { AuditCard(it, showRecord = false) }
        }
    }
}

private fun actionColor(a: String) = when {
    a.contains("creat") || a.contains("add") -> Color(0xFF2E7D32)
    a.contains("delet") || a.contains("cancel") -> Color(0xFFC62828)
    a.contains("lock") -> Color(0xFF6A1B9A)
    else -> Color(0xFF1565C0)
}

@Composable
fun AuditCard(r: AuditRow, showRecord: Boolean = true, onClick: (() -> Unit)? = null) {
    ElevatedCard(
        onClick = onClick ?: {}, enabled = onClick != null,
        colors = CardDefaults.elevatedCardColors(containerColor = MaterialTheme.colorScheme.surface, disabledContainerColor = MaterialTheme.colorScheme.surface),
        modifier = Modifier.fillMaxWidth(),
    ) {
        Column(Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                val col = actionColor(r.action)
                Surface(color = col.copy(alpha = 0.12f), shape = RoundedCornerShape(50)) {
                    Text(r.action.cap(), color = col, style = MaterialTheme.typography.labelMedium, modifier = Modifier.padding(horizontal = 10.dp, vertical = 3.dp))
                }
                Spacer(Modifier.width(8.dp))
                Text(r.userName ?: "System", style = MaterialTheme.typography.titleSmall, modifier = Modifier.weight(1f), maxLines = 1, overflow = TextOverflow.Ellipsis)
                Text(prettyDate(r.createdAt) + (r.createdAt?.drop(11)?.take(5)?.takeIf { it.length == 5 }?.let { " $it" } ?: ""), style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.outline)
            }
            if (showRecord) Text(listOfNotNull(r.module?.cap(), r.recordTitle?.takeIf { it.isNotBlank() } ?: r.recordId?.asText()?.let { "#$it" }).joinToString(" · "), style = MaterialTheme.typography.bodyMedium)
            r.changes.orEmpty().forEach { (field, ch) ->
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text(field.cap() + ": ", style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.SemiBold)
                    val old = ch.old?.asText().orEmpty()
                    if (old.isNotEmpty()) Text(old, style = MaterialTheme.typography.bodySmall, color = Color(0xFFC62828), textDecoration = TextDecoration.LineThrough, maxLines = 2, overflow = TextOverflow.Ellipsis, modifier = Modifier.weight(1f, fill = false))
                    if (old.isNotEmpty()) Text("  →  ", style = MaterialTheme.typography.bodySmall)
                    Text(ch.new?.asText().orEmpty().ifEmpty { "(empty)" }, style = MaterialTheme.typography.bodySmall, color = Color(0xFF2E7D32), maxLines = 2, overflow = TextOverflow.Ellipsis, modifier = Modifier.weight(1f, fill = false))
                }
            }
        }
    }
}
