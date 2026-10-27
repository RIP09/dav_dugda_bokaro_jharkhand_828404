package com.davdugda.sms.core.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.runtime.snapshots.SnapshotStateMap
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.davdugda.sms.core.data.*
import com.davdugda.sms.core.ui.*
import com.davdugda.sms.core.ui.components.*
import com.davdugda.sms.core.ui.theme.StatusColors
import java.time.LocalDate
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.launch
import java.time.YearMonth
import java.time.format.DateTimeFormatter

private val statuses = listOf("present" to "P", "absent" to "A", "late" to "L", "half_day" to "H", "leave" to "LV")

/** Classes a user may pick: admin = all; teacher = sections they teach or are class teacher of. */
class ClassScope(c: AppContainer) : BaseViewModel(c) {
    val classes = mutableStateOf<List<SchoolClass>>(emptyList())
    init {
        act {
            val all = api.classes().data
            val me = c.session.userJson?.let { c.json.decodeFromString(User.serializer(), it) }
            classes.value = if (me?.role == "teacher") {
                val mine = api.timetable(mine = 1).data.map { it.sectionId }.toSet()
                all.map { cl -> cl.copy(sections = cl.sections.filter { it.id in mine || it.teacherId == me.id }) }.filter { it.sections.isNotEmpty() }
            } else all
            null
        }
    }
}

// ================================ Take attendance =============================================

class TakeAttendanceViewModel(c: AppContainer) : BaseViewModel(c) {
    val sheet = mutableStateOf<Load<AttendanceSheet>?>(null)
    val marks: SnapshotStateMap<Int, String> = mutableStateMapOf()
    var date by mutableStateOf(today())
    var classId by mutableStateOf<Int?>(null)
    var sectionId by mutableStateOf<Int?>(null)

    fun loadSheet() {
        val s = sectionId ?: return
        sheet.value = Load.Loading
        viewModelScope.launch {
            sheet.value = try {
                val res = api.attendanceSheet(s, date).data!!
                marks.clear()
                res.rows.forEach { marks[it.studentId] = it.status ?: "present" }
                Load.Ok(res)
            } catch (e: CancellationException) {
                throw e
            } catch (e: Throwable) {
                Load.Err(c.errorMessage(e))
            }
        }
    }
    fun markAll(status: String) { marks.keys.toList().forEach { marks[it] = status } }
    fun save() {
        val s = sectionId ?: return
        act { api.saveAttendance(AttendanceSubmit(s, date, marks.map { (id, st) -> AttendanceRecord(id, st) })).message }
    }
}

@Composable
fun TakeAttendanceScreen() {
    val scope = screenViewModel { ClassScope(it) }
    val vm = screenViewModel { TakeAttendanceViewModel(it) }
    MessageEffect(vm); MessageEffect(scope)
    val rows = (vm.sheet.value as? Load.Ok)?.data?.rows ?: emptyList()
    val present = vm.marks.values.count { it == "present" || it == "late" }
    Scaffold(
        bottomBar = {
            if (rows.isNotEmpty()) Surface(tonalElevation = 3.dp) {
                Row(Modifier.fillMaxWidth().padding(16.dp), verticalAlignment = Alignment.CenterVertically) {
                    Column(Modifier.weight(1f)) {
                        Text("$present present · ${vm.marks.values.count { it == "absent" }} absent", fontWeight = FontWeight.SemiBold)
                        Text("of ${rows.size} students", style = MaterialTheme.typography.bodySmall)
                    }
                    Button(onClick = vm::save, enabled = !vm.busy) { Icon(Icons.Outlined.Save, null); Spacer(Modifier.width(6.dp)); Text("Save") }
                }
            }
        },
    ) { pad ->
        Column(Modifier.padding(pad)) {
            Column(Modifier.padding(horizontal = 16.dp, vertical = 8.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                ClassSectionPicker(scope.classes.value, vm.classId, vm.sectionId) { c, s -> vm.classId = c; vm.sectionId = s; vm.loadSheet() }
                DateField("Date", vm.date, { vm.date = it; vm.loadSheet() })
                if (rows.isNotEmpty()) Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    AssistChip(onClick = { vm.markAll("present") }, label = { Text("All present") }, leadingIcon = { Icon(Icons.Outlined.DoneAll, null) })
                    AssistChip(onClick = { vm.markAll("absent") }, label = { Text("All absent") })
                    if ((vm.sheet.value as? Load.Ok)?.data?.alreadyMarked == true) StatusChip("present", "Already taken – editing")
                }
            }
            when (val s = vm.sheet.value) {
                null -> EmptyState("Pick a class and section to take attendance", Icons.Outlined.FactCheck)
                is Load.Loading -> Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) { CircularProgressIndicator() }
                is Load.Err -> ErrorBox(s.message, vm::loadSheet)
                is Load.Ok -> ScreenList {
                    if (s.data.rows.isEmpty()) item { EmptyState("No students in this section") }
                    items(s.data.rows, key = { it.studentId }) { r ->
                        Card {
                            Row(Modifier.padding(horizontal = 12.dp, vertical = 10.dp), verticalAlignment = Alignment.CenterVertically) {
                                Text("${r.rollNo ?: ""}", Modifier.width(28.dp), style = MaterialTheme.typography.labelLarge)
                                Text(r.name ?: "", Modifier.weight(1f), maxLines = 1)
                                statuses.take(4).forEach { (st, label) ->
                                    val sel = vm.marks[r.studentId] == st
                                    val col = StatusColors.of(st)
                                    Box(
                                        Modifier.padding(start = 4.dp).size(34.dp).clip(RoundedCornerShape(10.dp))
                                            .background(if (sel) col else col.copy(alpha = 0.10f))
                                            .then(Modifier.clickableRow { vm.marks[r.studentId] = st }),
                                        contentAlignment = Alignment.Center,
                                    ) { Text(label, color = if (sel) Color.White else col, fontWeight = FontWeight.Bold) }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

// ================================ Staff attendance (admin) =====================================

class StaffAttendanceViewModel(c: AppContainer) : BaseViewModel(c) {
    val sheet = loadState<StaffSheet>()
    val marks: SnapshotStateMap<Int, String> = mutableStateMapOf()
    var date by mutableStateOf(today())
    init { refresh() }
    fun refresh() = load(sheet) { api.staffSheet(date).data!!.also { r -> marks.clear(); r.rows.forEach { marks[it.userId] = it.status ?: "present" } } }
    fun save() = act { api.saveStaffAttendance(StaffSubmit(date, marks.map { (id, s) -> StaffRecord(id, s) })).message }
}

@Composable
fun StaffAttendanceScreen() {
    val vm = screenViewModel { StaffAttendanceViewModel(it) }
    MessageEffect(vm)
    Scaffold(floatingActionButton = { ExtendedFloatingActionButton(onClick = vm::save, icon = { Icon(Icons.Outlined.Save, null) }, text = { Text("Save") }) }) { pad ->
        Column(Modifier.padding(pad)) {
            Box(Modifier.padding(16.dp, 8.dp)) { DateField("Date", vm.date, { vm.date = it; vm.refresh() }) }
            LoadContent(vm.sheet.value, vm::refresh) { s ->
                ScreenList {
                    items(s.rows, key = { it.userId }) { r ->
                        Card {
                            Column(Modifier.padding(12.dp)) {
                                Text(r.name, style = MaterialTheme.typography.titleSmall)
                                Text(r.designation ?: "", style = MaterialTheme.typography.bodySmall)
                                Spacer(Modifier.height(6.dp))
                                Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                                    statuses.forEach { (st, _) ->
                                        FilterChip(selected = vm.marks[r.userId] == st, onClick = { vm.marks[r.userId] = st }, label = { Text(st.cap()) })
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

// ================================ My attendance (parent / student / staff) ========================

class MyAttendanceViewModel(c: AppContainer) : BaseViewModel(c) {
    val report = loadState<AttendanceReport>()
    var month by mutableStateOf(YearMonth.now())
    fun refresh(childId: Int?) = load(report, silent = true) { api.myAttendance(month.toString(), childId).data!! }
}

@Composable
fun MyAttendanceScreen() {
    val vm = screenViewModel { MyAttendanceViewModel(it) }
    val child = LocalChild.current.value
    LaunchedEffect(child, vm.month) { vm.refresh(child) }
    LoadContent(vm.report.value, { vm.refresh(child) }) { r ->
        ScreenList {
            r.student?.let { item { Text("${it.name} · ${it.classLabel ?: ""}", style = MaterialTheme.typography.titleMedium) } }
            item {
                Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.primaryContainer)) {
                    Row(Modifier.fillMaxWidth().padding(18.dp), verticalAlignment = Alignment.CenterVertically) {
                        Box(contentAlignment = Alignment.Center) {
                            CircularProgressIndicator(progress = { (r.percentage / 100).toFloat() }, modifier = Modifier.size(86.dp), strokeWidth = 9.dp, trackColor = MaterialTheme.colorScheme.surface)
                            Text("${r.percentage}%", fontWeight = FontWeight.Bold)
                        }
                        Spacer(Modifier.width(18.dp))
                        Column {
                            Text("Overall attendance", style = MaterialTheme.typography.titleMedium)
                            Text("${r.present} present · ${r.absent} absent · ${r.leave} leave", style = MaterialTheme.typography.bodyMedium)
                            Text("${r.total} working days recorded", style = MaterialTheme.typography.bodySmall)
                        }
                    }
                }
            }
            item {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    IconButton(onClick = { vm.month = vm.month.minusMonths(1) }) { Icon(Icons.Outlined.ChevronLeft, "Previous") }
                    Text(vm.month.format(DateTimeFormatter.ofPattern("MMMM yyyy")), Modifier.weight(1f), style = MaterialTheme.typography.titleMedium)
                    IconButton(onClick = { vm.month = vm.month.plusMonths(1) }, enabled = vm.month < YearMonth.now()) { Icon(Icons.Outlined.ChevronRight, "Next") }
                }
            }
            item { MonthCalendar(vm.month, r.days.associate { it.date.take(10) to it.status }) }
            item {
                Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    listOf("present", "absent", "late", "leave").forEach { s ->
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Box(Modifier.size(10.dp).clip(RoundedCornerShape(5.dp)).background(StatusColors.of(s))); Spacer(Modifier.width(4.dp)); Text(s.cap(), style = MaterialTheme.typography.labelSmall)
                        }
                    }
                }
            }
            val absences = r.days.filter { it.status != "present" }
            if (absences.isNotEmpty()) {
                item { SectionHeader("This month's absences & late marks") }
                items(absences) { d -> InfoCard(prettyDate(d.date), subtitle = d.remark, trailing = { StatusChip(d.status) }) }
            }
        }
    }
}

@Composable
fun MonthCalendar(month: YearMonth, days: Map<String, String>) {
    val first = month.atDay(1)
    val offset = first.dayOfWeek.value - 1
    Card {
        Column(Modifier.padding(12.dp)) {
            Row { listOf("M", "T", "W", "T", "F", "S", "S").forEach { Text(it, Modifier.weight(1f), style = MaterialTheme.typography.labelMedium, color = MaterialTheme.colorScheme.onSurfaceVariant) } }
            val cells = List(offset) { null } + (1..month.lengthOfMonth()).map { month.atDay(it) }
            cells.chunked(7).forEach { week ->
                Row(Modifier.padding(vertical = 3.dp)) {
                    week.forEach { d ->
                        Box(Modifier.weight(1f).aspectRatio(1f).padding(2.dp), contentAlignment = Alignment.Center) {
                            if (d != null) {
                                val st = days[d.toString()]
                                val col = st?.let { StatusColors.of(it) }
                                Box(Modifier.fillMaxSize().clip(RoundedCornerShape(10.dp)).background(col?.copy(alpha = 0.85f) ?: Color.Transparent), contentAlignment = Alignment.Center) {
                                    Text("${d.dayOfMonth}", color = if (col != null) Color.White else if (d == LocalDate.now()) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurface,
                                        fontWeight = if (d == LocalDate.now()) FontWeight.Bold else FontWeight.Normal, style = MaterialTheme.typography.bodySmall)
                                }
                            }
                        }
                    }
                    repeat(7 - week.size) { Spacer(Modifier.weight(1f)) }
                }
            }
        }
    }
}
