package com.davdugda.sms.modules.attendance

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
