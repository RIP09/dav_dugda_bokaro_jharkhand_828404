package com.davdugda.sms.core.ui.screens

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.davdugda.sms.core.data.*
import com.davdugda.sms.core.ui.*
import com.davdugda.sms.core.ui.components.*
import java.time.LocalDate

class TimetableViewModel(c: AppContainer) : BaseViewModel(c) {
    val entries = loadState<List<TimetableEntry>>()
    val classes = mutableStateOf<List<SchoolClass>>(emptyList())
    val teachers = mutableStateOf<List<User>>(emptyList())
    var classId by mutableStateOf<Int?>(null)
    var sectionId by mutableStateOf<Int?>(null)

    fun loadClasses() = act { classes.value = api.classes().data; teachers.value = api.users("teacher").data; null }
    fun refresh(mine: Boolean, childId: Int?) = load(entries, silent = true) {
        when {
            sectionId != null -> api.timetable(sectionId = sectionId).data
            mine -> api.timetable(mine = 1, studentId = childId).data
            else -> emptyList()
        }
    }
    fun add(body: TimetableCreate, done: () -> Unit) = act(onDone = done) { api.addPeriod(body).message }
    fun delete(id: Int, after: () -> Unit) = act(onDone = after) { api.deletePeriod(id).message }
}

private val dayNames = listOf("Mon", "Tue", "Wed", "Thu", "Fri", "Sat")

@Composable
fun TimetableScreen() {
    val vm = screenViewModel { TimetableViewModel(it) }
    MessageEffect(vm)
    val user = LocalUser.current
    val child = LocalChild.current.value
    val canPick = user.isAdmin || user.isTeacher
    val mine = !user.isAdmin
    var day by remember { mutableIntStateOf(LocalDate.now().dayOfWeek.value.coerceAtMost(6)) }
    var adding by remember { mutableStateOf(false) }
    LaunchedEffect(child) { if (canPick) vm.loadClasses(); vm.refresh(mine, child) }

    Scaffold(floatingActionButton = {
        if (user.isAdmin && vm.sectionId != null) ExtendedFloatingActionButton(onClick = { adding = true }, icon = { Icon(Icons.Outlined.Add, null) }, text = { Text("Add period") })
    }) { pad ->
        Column(Modifier.padding(pad)) {
            if (canPick) Column(Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                if (user.isTeacher) Text(if (vm.sectionId == null) "Showing: my periods" else "Showing: class timetable", style = MaterialTheme.typography.labelLarge)
                ClassSectionPicker(vm.classes.value, vm.classId, vm.sectionId, allowAll = user.isTeacher) { c, s -> vm.classId = c; vm.sectionId = s; vm.refresh(mine, child) }
            }
            ScrollableTabRow(selectedTabIndex = day - 1, edgePadding = 12.dp, containerColor = MaterialTheme.colorScheme.background) {
                dayNames.forEachIndexed { i, d -> Tab(selected = day == i + 1, onClick = { day = i + 1 }, text = { Text(d) }) }
            }
            if (user.isAdmin && vm.sectionId == null) EmptyState("Pick a class and section to see or edit its timetable", Icons.Outlined.Schedule)
            else LoadContent(vm.entries.value, { vm.refresh(mine, child) }) { list ->
                val today = list.filter { it.day == day }.sortedBy { it.startTime }
                ScreenList {
                    if (today.isEmpty()) item { EmptyState("No periods on this day") }
                    items(today, key = { it.id }) { e ->
                        Card {
                            Row(Modifier.padding(14.dp), verticalAlignment = Alignment.CenterVertically) {
                                Column(Modifier.width(64.dp)) {
                                    Text(e.startTime, fontWeight = FontWeight.Bold)
                                    Text(e.endTime, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                }
                                VerticalDivider(Modifier.height(40.dp).padding(horizontal = 8.dp), color = MaterialTheme.colorScheme.primary, thickness = 3.dp)
                                Column(Modifier.weight(1f)) {
                                    Text(e.subjectName ?: "", style = MaterialTheme.typography.titleMedium)
                                    Text(listOfNotNull(if (user.isTeacher && vm.sectionId == null) e.classLabel else e.teacherName, e.room?.let { "Room $it" }).joinToString(" · "),
                                        style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                }
                                if (user.isAdmin) IconButton(onClick = { vm.delete(e.id) { vm.refresh(mine, child) } }) { Icon(Icons.Outlined.DeleteOutline, "Remove") }
                            }
                        }
                    }
                }
            }
        }
    }
    if (adding) {
        val cl = vm.classes.value.firstOrNull { it.id == vm.classId }
        var subject by remember { mutableStateOf<Int?>(null) }
        var teacher by remember { mutableStateOf<Int?>(null) }
        var d by remember { mutableIntStateOf(day) }
        var start by remember { mutableStateOf("08:00") }
        var end by remember { mutableStateOf("08:40") }
        FormDialog("Add period", busy = vm.busy, onDismiss = { adding = false }, onConfirm = {
            val s = subject; val sec = vm.sectionId
            if (s == null || sec == null) vm.message = "Pick a subject"
            else vm.add(TimetableCreate(sec, s, teacher ?: cl?.subjects?.firstOrNull { it.id == s }?.teacherId, d, start.trim(), end.trim())) { adding = false; vm.refresh(mine, child) }
        }) {
            Dropdown("Day", dayNames.mapIndexed { i, n -> (i + 1) to n }, d, { d = it })
            Dropdown("Subject", (cl?.subjects ?: emptyList()).map { it.id to it.name }, subject, { subject = it })
            Dropdown("Teacher (blank = subject teacher)", listOf<Pair<Int?, String>>(null to "Subject teacher") + vm.teachers.value.map { it.id to it.name }, teacher, { teacher = it })
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                FormField("Start (HH:MM)", start, { start = it }, Modifier.weight(1f))
                FormField("End (HH:MM)", end, { end = it }, Modifier.weight(1f))
            }
        }
    }
}
