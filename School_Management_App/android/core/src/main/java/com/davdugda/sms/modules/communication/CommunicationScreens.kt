package com.davdugda.sms.modules.communication

import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.launch

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.davdugda.sms.core.data.*
import com.davdugda.sms.core.ui.*
import com.davdugda.sms.core.ui.components.*
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

// ================================ Notices ======================================================

class NoticesViewModel(c: AppContainer) : BaseViewModel(c) {
    val list = loadState<List<Notice>>()
    init { refresh() }
    fun refresh() = load(list, silent = true) { api.notices().data }
    fun post(body: NoticeCreate, done: () -> Unit) = act(onDone = { done(); refresh() }) { api.createNotice(body).message }
    fun delete(n: Notice) = act(onDone = ::refresh) { api.deleteNotice(n.id).message }
    val readIds = mutableStateListOf<Int>()
    var reads by mutableStateOf<kotlinx.serialization.json.JsonElement?>(null)
    /** Marks the notice as read (API v2) and, for staff, loads who has read it. */
    fun opened(n: Notice, canSeeReads: Boolean) {
        reads = null
        viewModelScope.launch {
            if (n.id !in readIds) runCatching { api.readNotice(n.id) }.onSuccess { readIds.add(n.id) }
            if (canSeeReads) reads = runCatching { api.noticeReads(n.id).data }.getOrNull()
        }
    }
}

@Composable
fun NoticesScreen() {
    val vm = screenViewModel { NoticesViewModel(it) }
    MessageEffect(vm)
    val user = LocalUser.current
    val canPost = user.isAdmin || user.isTeacher
    var posting by remember { mutableStateOf(false) }
    var open by remember { mutableStateOf<Notice?>(null) }
    Scaffold(floatingActionButton = { if (canPost) ExtendedFloatingActionButton(onClick = { posting = true }, icon = { Icon(Icons.Outlined.Campaign, null) }, text = { Text("New notice") }) }) { pad ->
        Box(Modifier.padding(pad)) {
            LoadContent(vm.list.value, vm::refresh) { list ->
                ScreenList {
                    if (list.isEmpty()) item { EmptyState("No notices") }
                    items(list, key = { it.id }) { n ->
                        InfoCard(n.title, subtitle = "${prettyDate(n.publishDate)} · ${n.authorName ?: "School office"}", supporting = n.body,
                            leading = { IconBadge(if (n.isPinned) Icons.Outlined.PushPin else Icons.Outlined.Campaign, Color(0xFFE53935)) },
                            trailing = {
                                Column(horizontalAlignment = androidx.compose.ui.Alignment.End) {
                                    if (n.isRead == false && n.id !in vm.readIds) Badge { Text("New") }
                                    if (n.audience != "all") StatusChip("leave", n.audience.cap())
                                }
                            },
                            onClick = { open = n; vm.opened(n, canPost) })
                    }
                }
            }
        }
    }
    open?.let { n ->
        AlertDialog(onDismissRequest = { open = null }, title = { Text(n.title) }, text = {
            Column(Modifier.verticalScrollSafe()) {
                Text("${prettyDate(n.publishDate)} · ${n.authorName ?: ""} · for ${n.audience}", style = MaterialTheme.typography.bodySmall)
                Spacer(Modifier.height(8.dp)); Text(n.body)
                vm.reads?.let { r ->
                    Spacer(Modifier.height(12.dp)); HorizontalDivider(); Spacer(Modifier.height(8.dp))
                    Text("Read by", style = MaterialTheme.typography.labelLarge)
                    com.davdugda.sms.modules.generic.JsonResultView(r)
                }
            }
        }, confirmButton = { TextButton(onClick = { open = null }) { Text("Close") } },
            dismissButton = { if (user.isAdmin) TextButton(onClick = { vm.delete(n); open = null }) { Text("Delete", color = MaterialTheme.colorScheme.error) } })
    }
    if (posting) {
        var title by remember { mutableStateOf("") }
        var body by remember { mutableStateOf("") }
        var aud by remember { mutableStateOf(if (user.isAdmin) "all" else "parents") }
        var pin by remember { mutableStateOf(false) }
        FormDialog("New notice", confirmText = "Publish", busy = vm.busy, onDismiss = { posting = false }, onConfirm = {
            if (title.isBlank() || body.isBlank()) vm.message = "Title and message are required" else vm.post(NoticeCreate(title, body, aud, pin)) { posting = false }
        }) {
            FormField("Title", title, { title = it })
            FormField("Message", body, { body = it }, multiline = true)
            val opts = if (user.isAdmin) listOf("all" to "Everyone", "staff" to "Staff only", "parents" to "Parents", "students" to "Students") else listOf("parents" to "Parents", "students" to "Students")
            Dropdown("Who should see it", opts, aud, { aud = it })
            if (user.isAdmin) Row(verticalAlignment = androidx.compose.ui.Alignment.CenterVertically) { Checkbox(pin, { pin = it }); Text("Pin to top") }
        }
    }
}

// ================================ Events & holidays =============================================

class EventsViewModel(c: AppContainer) : BaseViewModel(c) {
    val list = loadState<List<SchoolEvent>>()
    init { refresh() }
    fun refresh() = load(list, silent = true) { api.events().data }
    fun add(body: EventCreate, done: () -> Unit) = act(onDone = { done(); refresh() }) { api.createEvent(body).message }
    fun delete(e: SchoolEvent) = act(onDone = ::refresh) { api.deleteEvent(e.id).message }
}

@Composable
fun EventsScreen() {
    val vm = screenViewModel { EventsViewModel(it) }
    MessageEffect(vm)
    val user = LocalUser.current
    var adding by remember { mutableStateOf(false) }
    Scaffold(floatingActionButton = { if (user.isAdmin) ExtendedFloatingActionButton(onClick = { adding = true }, icon = { Icon(Icons.Outlined.Add, null) }, text = { Text("Add event") }) }) { pad ->
        Box(Modifier.padding(pad)) {
            LoadContent(vm.list.value, vm::refresh) { list ->
                val (upcoming, past) = list.partition { (it.endDate ?: it.startDate) >= today() }
                ScreenList {
                    item { SectionHeader("Upcoming") }
                    if (upcoming.isEmpty()) item { EmptyState("Nothing scheduled") }
                    items(upcoming, key = { it.id }) { e -> EventCard(e, user.isAdmin) { vm.delete(e) } }
                    if (past.isNotEmpty()) {
                        item { SectionHeader("Earlier") }
                        items(past.reversed(), key = { it.id }) { e -> EventCard(e, user.isAdmin) { vm.delete(e) } }
                    }
                }
            }
        }
    }
    if (adding) {
        var title by remember { mutableStateOf("") }
        var type by remember { mutableStateOf("event") }
        var start by remember { mutableStateOf(today()) }
        var end by remember { mutableStateOf("") }
        var loc by remember { mutableStateOf("") }
        var desc by remember { mutableStateOf("") }
        FormDialog("Add to school calendar", busy = vm.busy, onDismiss = { adding = false }, onConfirm = { vm.add(EventCreate(title, desc.ifBlank { null }, start, end.ifBlank { null }, loc.ifBlank { null }, type)) { adding = false } }) {
            FormField("Title", title, { title = it })
            Dropdown("Type", listOf("event" to "Event", "holiday" to "Holiday", "exam" to "Exam", "meeting" to "Meeting / PTM"), type, { type = it })
            DateField("From", start, { start = it }); DateField("To (optional)", end, { end = it })
            FormField("Venue", loc, { loc = it }); FormField("Details", desc, { desc = it }, multiline = true)
        }
    }
}

@Composable
private fun EventCard(e: SchoolEvent, canDelete: Boolean, onDelete: () -> Unit) {
    val (icon, color) = when (e.type) {
        "holiday" -> Icons.Outlined.BeachAccess to Color(0xFF039BE5)
        "exam" -> Icons.Outlined.Quiz to Color(0xFFF4511E)
        "meeting" -> Icons.Outlined.Groups to Color(0xFF5E35B1)
        else -> Icons.Outlined.Celebration to Color(0xFFFB8C00)
    }
    InfoCard(e.title, subtitle = prettyDate(e.startDate) + (e.endDate?.let { " – " + prettyDate(it) } ?: "") + (e.location?.let { " · $it" } ?: ""),
        supporting = e.description, leading = { IconBadge(icon, color) },
        trailing = { if (canDelete) IconButton(onClick = onDelete) { Icon(Icons.Outlined.DeleteOutline, "Delete") } else StatusChip("leave", e.type.cap()) })
}

// ================================ Messages =====================================================

class MessagesViewModel(c: AppContainer) : BaseViewModel(c) {
    val list = loadState<List<Message>>()
    val contacts = mutableStateOf<List<UserLite>>(emptyList())
    var box by mutableStateOf("inbox")
    init { refresh() }
    fun refresh() = load(list, silent = true) { api.messages(box).data }
    fun loadContacts() = act { contacts.value = api.contacts().data; null }
    fun open(m: Message, show: (Message) -> Unit) = act { show(api.openMessage(m.id).data ?: m); null }
    fun send(body: MessageCreate, done: () -> Unit) = act(onDone = { done(); refresh() }) { api.sendMessage(body).message }
}

@Composable
fun MessagesScreen() {
    val vm = screenViewModel { MessagesViewModel(it) }
    MessageEffect(vm)
    var composing by remember { mutableStateOf<Pair<Int?, String>?>(null) }
    var reading by remember { mutableStateOf<Message?>(null) }
    Scaffold(floatingActionButton = { ExtendedFloatingActionButton(onClick = { composing = null to ""; vm.loadContacts() }, icon = { Icon(Icons.Outlined.Edit, null) }, text = { Text("Write") }) }) { pad ->
        Column(Modifier.padding(pad)) {
            TabRow(selectedTabIndex = if (vm.box == "inbox") 0 else 1, containerColor = MaterialTheme.colorScheme.background) {
                Tab(vm.box == "inbox", { vm.box = "inbox"; vm.refresh() }, text = { Text("Inbox") })
                Tab(vm.box == "sent", { vm.box = "sent"; vm.refresh() }, text = { Text("Sent") })
            }
            LoadContent(vm.list.value, vm::refresh) { list ->
                ScreenList {
                    if (list.isEmpty()) item { EmptyState(if (vm.box == "inbox") "No messages yet" else "You haven't sent anything", Icons.Outlined.Forum) }
                    items(list, key = { it.id }) { m ->
                        val who = if (vm.box == "inbox") m.senderName else m.receiverName
                        InfoCard(
                            (if (vm.box == "inbox" && m.readAt == null) "● " else "") + (m.subject ?: "(no subject)"),
                            subtitle = "${if (vm.box == "inbox") "From" else "To"} ${who ?: ""} · ${shortDate(m.createdAt)}",
                            supporting = m.body, leading = { Avatar(who ?: "?") },
                            onClick = { vm.open(m) { reading = it; if (vm.box == "inbox") vm.refresh() } },
                        )
                    }
                }
            }
        }
    }
    reading?.let { m ->
        AlertDialog(onDismissRequest = { reading = null }, title = { Text(m.subject ?: "(no subject)") }, text = {
            Column(Modifier.verticalScrollSafe()) {
                Text("From ${m.senderName} to ${m.receiverName} · ${prettyDate(m.createdAt)}", style = MaterialTheme.typography.bodySmall)
                Spacer(Modifier.height(8.dp)); Text(m.body)
            }
        }, confirmButton = {
            if (vm.box == "inbox") TextButton(onClick = { composing = m.senderId to "Re: ${m.subject ?: ""}"; vm.loadContacts(); reading = null }) { Text("Reply") }
        }, dismissButton = { TextButton(onClick = { reading = null }) { Text("Close") } })
    }
    composing?.let { (to0, subj0) ->
        var to by remember { mutableStateOf(to0) }
        var subject by remember { mutableStateOf(subj0) }
        var body by remember { mutableStateOf("") }
        FormDialog("New message", confirmText = "Send", busy = vm.busy, onDismiss = { composing = null }, onConfirm = {
            val r = to
            if (r == null || body.isBlank()) vm.message = "Pick who to send to and write a message" else vm.send(MessageCreate(r, subject.ifBlank { null }, body)) { composing = null }
        }) {
            Dropdown("To", vm.contacts.value.map { it.id to "${it.name} (${it.role?.cap() ?: ""})" }, to, { to = it })
            FormField("Subject", subject, { subject = it })
            FormField("Message", body, { body = it }, multiline = true)
        }
    }
}
