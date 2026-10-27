package com.davdugda.sms.modules.communication

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


class NotificationsViewModel(c: AppContainer) : BaseViewModel(c) {
    val state = loadState<List<AppNotification>>()
    var unread by mutableStateOf(0)
    fun refresh() = load(state, silent = true) { val r = api.notifications(); unread = r.meta?.unread ?: r.data.count { it.readAt == null }; r.data }
    fun read(n: AppNotification) { if (n.readAt == null) act { api.readNotification(n.id); refresh(); null } }
    fun readAll() = act("All marked as read.") { api.readAllNotifications(); refresh(); null }
}

@Composable
fun NotificationsScreen() {
    val vm = screenViewModel { NotificationsViewModel(it) }
    val navigate = LocalNavigate.current
    MessageEffect(vm)
    LaunchedEffect(Unit) { vm.refresh() }
    LoadContent(vm.state.value, onRetry = { vm.refresh() }) { list ->
        ScreenList {
            item {
                SectionHeader(if (vm.unread > 0) "${vm.unread} unread" else "All caught up") {
                    if (vm.unread > 0) TextButton(onClick = { vm.readAll() }) { Text("Mark all read") }
                }
            }
            if (list.isEmpty()) item { EmptyState("No notifications yet.", Icons.Outlined.NotificationsNone) }
            items(list, key = { it.id }) { n ->
                val unread = n.readAt == null
                InfoCard(
                    n.title, subtitle = prettyDate(n.createdAt), supporting = n.body,
                    leading = { IconBadge(iconFor(null, n.module ?: "notifications"), if (unread) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.outline) },
                    trailing = { if (unread) Badge { Text("New") } },
                    onClick = {
                        vm.read(n)
                        val id = n.recordId?.asText().orEmpty()
                        if (n.module != null && id.isNotEmpty()) navigate(routeForRecord(n.module, id))
                    },
                )
            }
        }
    }
}

/** Where a module record opens (notices open the notice board in family apps). */
fun routeForRecord(module: String, id: String): String = when (module) {
    "notices" -> Feature.Notices.route
    "messages" -> Feature.Messages.route
    "events" -> Feature.Events.route
    else -> Routes.record(module, id)
}
