package com.davdugda.sms.core.ui.screens

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

// ================================ Library =====================================================

class LibraryViewModel(c: AppContainer) : BaseViewModel(c) {
    val books = loadState<List<Book>>()
    val issues = loadState<List<BookIssue>>()
    val members = mutableStateOf<List<Student>>(emptyList())
    var search by mutableStateOf("")
    init { refresh() }
    fun refresh() {
        load(books, silent = true) { api.books(search.ifBlank { null }).data }
        load(issues, silent = true) { api.issues().data }
    }
    fun loadMembers(q: String) = act { members.value = api.students(search = q.ifBlank { null }).data.take(30); null }
    fun addBook(b: BookCreate, done: () -> Unit) = act(onDone = { done(); refresh() }) { api.createBook(b).message }
    fun issue(bookId: Int, userId: Int, done: () -> Unit) = act(onDone = { done(); refresh() }) { api.issueBook(IssueCreate(bookId, userId)).message }
    fun giveBack(i: BookIssue) = act(onDone = ::refresh) { api.returnBook(i.id).message }
}

@Composable
fun LibraryScreen() {
    val vm = screenViewModel { LibraryViewModel(it) }
    MessageEffect(vm)
    val user = LocalUser.current
    val manage = user.isAdmin || user.isLibrarian
    var tab by remember { mutableIntStateOf(if (manage) 0 else 1) }
    var adding by remember { mutableStateOf(false) }
    var issuing by remember { mutableStateOf<Book?>(null) }
    Scaffold(floatingActionButton = { if (manage && tab == 0) ExtendedFloatingActionButton(onClick = { adding = true }, icon = { Icon(Icons.Outlined.LibraryAdd, null) }, text = { Text("Add book") }) }) { pad ->
        Column(Modifier.padding(pad)) {
            TabRow(selectedTabIndex = tab, containerColor = MaterialTheme.colorScheme.background) {
                Tab(tab == 0, { tab = 0 }, text = { Text("Catalogue") })
                Tab(tab == 1, { tab = 1 }, text = { Text(if (manage) "Issued books" else "My books") })
            }
            if (tab == 0) {
                Box(Modifier.padding(16.dp, 8.dp)) { SearchField(vm.search, { vm.search = it; vm.refresh() }, "Search title, author, ISBN") }
                LoadContent(vm.books.value, vm::refresh) { list ->
                    ScreenList {
                        if (list.isEmpty()) item { EmptyState("No books found") }
                        items(list, key = { it.id }) { b ->
                            InfoCard(b.title, subtitle = listOfNotNull(b.author, b.category).joinToString(" · "),
                                supporting = "${b.availableCopies} of ${b.totalCopies} available${b.rack?.let { " · Rack $it" } ?: ""}",
                                leading = { IconBadge(Icons.Outlined.Book, Color(0xFF6D4C41)) },
                                trailing = { if (manage && b.availableCopies > 0) TextButton(onClick = { issuing = b; vm.loadMembers("") }) { Text("Issue") } else if (b.availableCopies == 0) StatusChip("absent", "Out") })
                        }
                    }
                }
            } else {
                LoadContent(vm.issues.value, vm::refresh) { list ->
                    ScreenList {
                        if (list.isEmpty()) item { EmptyState("No books issued") }
                        items(list, key = { it.id }) { i ->
                            InfoCard(i.bookTitle ?: "", subtitle = (if (manage) "${i.memberName} · " else "") + "Issued ${shortDate(i.issuedOn)} · due ${prettyDate(i.dueOn)}",
                                supporting = if (i.fine > 0) "Fine ${rupees(i.fine)}" else i.returnedOn?.let { "Returned ${prettyDate(it)}" },
                                leading = { IconBadge(Icons.Outlined.Book, Color(0xFF6D4C41)) },
                                trailing = { if (manage && i.status != "returned") TextButton(onClick = { vm.giveBack(i) }) { Text("Return") } else StatusChip(i.status) })
                        }
                    }
                }
            }
        }
    }
    if (adding) {
        var title by remember { mutableStateOf("") }; var author by remember { mutableStateOf("") }; var isbn by remember { mutableStateOf("") }
        var cat by remember { mutableStateOf("") }; var copies by remember { mutableStateOf("1") }; var rack by remember { mutableStateOf("") }
        FormDialog("Add book", busy = vm.busy, onDismiss = { adding = false }, onConfirm = { vm.addBook(BookCreate(title, author.ifBlank { null }, isbn.ifBlank { null }, cat.ifBlank { null }, copies.toIntOrNull() ?: 1, rack.ifBlank { null })) { adding = false } }) {
            FormField("Title", title, { title = it }); FormField("Author", author, { author = it }); FormField("ISBN", isbn, { isbn = it })
            FormField("Category", cat, { cat = it }); FormField("Copies", copies, { copies = it }, number = true); FormField("Rack", rack, { rack = it })
        }
    }
    issuing?.let { b ->
        var q by remember { mutableStateOf("") }
        var member by remember { mutableStateOf<Int?>(null) }
        FormDialog("Issue \"${b.title}\"", confirmText = "Issue", busy = vm.busy, onDismiss = { issuing = null }, onConfirm = { member?.let { vm.issue(b.id, it) { issuing = null } } ?: run { vm.message = "Pick a student" } }) {
            FormField("Search student", q, { q = it; vm.loadMembers(it) })
            Dropdown("Student", vm.members.value.map { it.userId to "${it.displayName} (${it.classLabel ?: ""})" }, member, { member = it })
            Text("Due back in 14 days. Late fine is charged per day on return.", style = MaterialTheme.typography.bodySmall)
        }
    }
}

// ================================ Transport ====================================================

class TransportViewModel(c: AppContainer) : BaseViewModel(c) {
    val list = loadState<List<TransportRoute>>()
    val riders = mutableStateOf<List<Student>?>(null)
    init { refresh() }
    fun refresh() = load(list, silent = true) { api.routes().data }
    fun loadRiders(r: TransportRoute) = act { riders.value = api.routeStudents(r.id).data; null }
    fun add(body: RouteCreate, done: () -> Unit) = act(onDone = { done(); refresh() }) { api.createRoute(body).message }
}

@Composable
fun TransportScreen() {
    val vm = screenViewModel { TransportViewModel(it) }
    MessageEffect(vm)
    val user = LocalUser.current
    val staffSide = user.isAdmin || user.isStaffMember
    var adding by remember { mutableStateOf(false) }
    var open by remember { mutableStateOf<TransportRoute?>(null) }
    Scaffold(floatingActionButton = { if (user.isAdmin) ExtendedFloatingActionButton(onClick = { adding = true }, icon = { Icon(Icons.Outlined.AddRoad, null) }, text = { Text("Add route") }) }) { pad ->
        Box(Modifier.padding(pad)) {
            LoadContent(vm.list.value, vm::refresh) { list ->
                val sorted = list.sortedByDescending { it.isMine }
                ScreenList {
                    if (list.isEmpty()) item { EmptyState("No bus routes") }
                    items(sorted, key = { it.id }) { r ->
                        ElevatedCard(onClick = { open = r; if (staffSide) vm.loadRiders(r) }, modifier = Modifier.fillMaxWidth(),
                            colors = CardDefaults.elevatedCardColors(containerColor = if (r.isMine) MaterialTheme.colorScheme.primaryContainer else MaterialTheme.colorScheme.surface)) {
                            Column(Modifier.padding(14.dp)) {
                                Row {
                                    IconBadge(Icons.Outlined.DirectionsBus, Color(0xFFFFB300))
                                    Spacer(Modifier.width(12.dp))
                                    Column(Modifier.weight(1f)) {
                                        Text(r.name, style = MaterialTheme.typography.titleMedium)
                                        Text("${r.vehicleNo ?: ""} · ${rupees(r.fare)}/month", style = MaterialTheme.typography.bodySmall)
                                    }
                                    if (r.isMine) StatusChip("present", "Your bus")
                                }
                                Spacer(Modifier.height(8.dp))
                                Text("Driver: ${r.driverName ?: "-"} · ${r.driverPhone ?: ""}", style = MaterialTheme.typography.bodyMedium)
                                Text("Pickup ${r.pickupTime ?: "-"} · Drop ${r.dropTime ?: "-"}", style = MaterialTheme.typography.bodyMedium)
                                r.stops?.takeIf { it.isNotEmpty() }?.let { Text("Stops: " + it.joinToString(" → "), style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant) }
                                if (staffSide) Text("${r.studentsCount} students", style = MaterialTheme.typography.labelMedium)
                            }
                        }
                    }
                }
            }
        }
    }
    open?.takeIf { staffSide }?.let { r ->
        AlertDialog(onDismissRequest = { open = null; vm.riders.value = null }, title = { Text(r.name) }, text = {
            Column(Modifier.verticalScrollSafe()) {
                val rs = vm.riders.value
                if (rs == null) CircularProgressIndicator() else if (rs.isEmpty()) Text("No students on this route")
                rs?.forEach { s -> Text("• ${s.displayName} (${s.classLabel ?: ""})") }
            }
        }, confirmButton = { TextButton(onClick = { open = null; vm.riders.value = null }) { Text("Close") } })
    }
    if (adding) {
        var name by remember { mutableStateOf("") }; var vehicle by remember { mutableStateOf("") }; var driver by remember { mutableStateOf("") }
        var phone by remember { mutableStateOf("") }; var fare by remember { mutableStateOf("") }; var stops by remember { mutableStateOf("") }
        FormDialog("Add bus route", busy = vm.busy, onDismiss = { adding = false }, onConfirm = {
            vm.add(RouteCreate(name, vehicle.ifBlank { null }, driver.ifBlank { null }, phone.ifBlank { null }, fare.toDoubleOrNull() ?: 0.0, stops.split(",").map { it.trim() }.filter { it.isNotEmpty() })) { adding = false }
        }) {
            FormField("Route name", name, { name = it }); FormField("Vehicle no.", vehicle, { vehicle = it }); FormField("Driver", driver, { driver = it })
            FormField("Driver phone", phone, { phone = it }, number = true); FormField("Monthly fare (₹)", fare, { fare = it }, number = true)
            FormField("Stops, comma separated", stops, { stops = it })
        }
    }
}

// ================================ Leave ========================================================

class LeavesViewModel(c: AppContainer) : BaseViewModel(c) {
    val list = loadState<List<LeaveRequest>>()
    var scope by mutableStateOf("mine")
    fun refresh() = load(list, silent = true) { api.leaves(scope).data }
    fun apply(body: LeaveCreate, done: () -> Unit) = act(onDone = { done(); refresh() }) { api.applyLeave(body).message }
    fun review(l: LeaveRequest, status: String) = act(onDone = ::refresh) { api.reviewLeave(l.id, LeaveReview(status, null)).message }
    fun cancel(l: LeaveRequest) = act(onDone = ::refresh) { api.cancelLeave(l.id).message }
}

@Composable
fun LeavesScreen() {
    val vm = screenViewModel { LeavesViewModel(it) }
    MessageEffect(vm)
    val user = LocalUser.current
    val child = LocalChild.current.value
    val reviewer = user.isAdmin || user.isTeacher
    var applying by remember { mutableStateOf(false) }
    LaunchedEffect(Unit) { vm.scope = if (user.isAdmin) "review" else "mine"; vm.refresh() }
    Scaffold(floatingActionButton = { if (!user.isAdmin) ExtendedFloatingActionButton(onClick = { applying = true }, icon = { Icon(Icons.Outlined.Add, null) }, text = { Text("Apply for leave") }) }) { pad ->
        Column(Modifier.padding(pad)) {
            if (user.isTeacher) TabRow(selectedTabIndex = if (vm.scope == "mine") 0 else 1, containerColor = MaterialTheme.colorScheme.background) {
                Tab(vm.scope == "mine", { vm.scope = "mine"; vm.refresh() }, text = { Text("My leave") })
                Tab(vm.scope == "review", { vm.scope = "review"; vm.refresh() }, text = { Text("Students' requests") })
            }
            LoadContent(vm.list.value, vm::refresh) { list ->
                ScreenList {
                    if (list.isEmpty()) item { EmptyState("No leave requests", Icons.Outlined.BeachAccess) }
                    items(list, key = { it.id }) { l ->
                        Card {
                            Column(Modifier.padding(14.dp)) {
                                Row {
                                    Column(Modifier.weight(1f)) {
                                        Text(l.studentName?.let { "$it (by ${l.applicantName})" } ?: l.applicantName ?: "", style = MaterialTheme.typography.titleSmall)
                                        Text("${prettyDate(l.fromDate)} – ${prettyDate(l.toDate)} · ${l.days} day(s) · ${l.type.cap()}", style = MaterialTheme.typography.bodySmall)
                                    }
                                    StatusChip(l.status)
                                }
                                Spacer(Modifier.height(6.dp)); Text(l.reason)
                                l.reviewerName?.let { Text("Reviewed by $it${l.reviewNote?.let { n -> ": $n" } ?: ""}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant) }
                                if (l.status == "pending") Row(horizontalArrangement = Arrangement.End, modifier = Modifier.fillMaxWidth()) {
                                    if (reviewer && vm.scope == "review") {
                                        TextButton(onClick = { vm.review(l, "rejected") }) { Text("Reject", color = MaterialTheme.colorScheme.error) }
                                        Button(onClick = { vm.review(l, "approved") }) { Text("Approve") }
                                    } else if (l.userId == user.id) TextButton(onClick = { vm.cancel(l) }) { Text("Cancel request") }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    if (applying) {
        var type by remember { mutableStateOf("sick") }
        var from by remember { mutableStateOf(today()) }
        var to by remember { mutableStateOf(today()) }
        var reason by remember { mutableStateOf("") }
        FormDialog(if (user.isParent) "Leave for your child" else "Apply for leave", confirmText = "Send", busy = vm.busy, onDismiss = { applying = false }, onConfirm = {
            if (reason.isBlank()) vm.message = "Please write a reason" else vm.apply(LeaveCreate(type, from, to, reason, if (user.isParent) child else null)) { applying = false }
        }) {
            if (user.isParent) Text("For: " + (user.children.firstOrNull { it.id == child }?.displayName ?: ""), fontWeight = FontWeight.SemiBold)
            Dropdown("Type", listOf("sick" to "Sick", "casual" to "Casual", "other" to "Other"), type, { type = it })
            DateField("From", from, { from = it }); DateField("To", to, { to = it })
            FormField("Reason", reason, { reason = it }, multiline = true)
        }
    }
}
