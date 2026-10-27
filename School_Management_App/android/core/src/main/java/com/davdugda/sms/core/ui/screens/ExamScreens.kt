package com.davdugda.sms.core.ui.screens

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.itemsIndexed
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.runtime.snapshots.SnapshotStateMap
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewModelScope
import com.davdugda.sms.core.data.*
import com.davdugda.sms.core.ui.*
import com.davdugda.sms.core.ui.components.*
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.launch

// ================================ Exams & date sheet ===========================================

class ExamsViewModel(c: AppContainer) : BaseViewModel(c) {
    val exams = loadState<List<Exam>>()
    val schedule = mutableStateOf<List<ExamSchedule>>(emptyList())
    val classes = mutableStateOf<List<SchoolClass>>(emptyList())
    var selected by mutableStateOf<Exam?>(null)
    var classId by mutableStateOf<Int?>(null)
    init { refresh() }
    fun refresh() = load(exams, silent = true) { api.exams().data }
    fun loadClasses() = act { classes.value = api.classes().data; null }
    fun open(e: Exam, childId: Int?) { selected = e; act { schedule.value = api.examSchedules(e.id, classId, childId).data; null } }
    fun create(body: ExamCreate, done: () -> Unit) = act(onDone = { done(); refresh() }) { api.createExam(body).message }
    fun publish(e: Exam) = act(onDone = ::refresh) { api.togglePublish(e.id).message }
}

@Composable
fun ExamsScreen() {
    val vm = screenViewModel { ExamsViewModel(it) }
    MessageEffect(vm)
    val user = LocalUser.current
    val child = LocalChild.current.value
    val staffSide = user.isAdmin || user.isStaffMember
    var adding by remember { mutableStateOf(false) }
    LaunchedEffect(Unit) { if (staffSide) vm.loadClasses() }
    Scaffold(floatingActionButton = { if (user.isAdmin) ExtendedFloatingActionButton(onClick = { adding = true }, icon = { Icon(Icons.Outlined.Add, null) }, text = { Text("New exam") }) }) { pad ->
        Box(Modifier.padding(pad)) {
            LoadContent(vm.exams.value, vm::refresh) { list ->
                ScreenList {
                    if (staffSide) item {
                        Dropdown("Class for date sheet", vm.classes.value.map { it.id to it.name }, vm.classId, { vm.classId = it; vm.selected?.let { e -> vm.open(e, child) } })
                    }
                    if (list.isEmpty()) item { EmptyState("No exams yet") }
                    items(list, key = { it.id }) { e ->
                        InfoCard(e.name, subtitle = listOfNotNull(e.term, e.startDate?.let { prettyDate(it) + (e.endDate?.let { d -> " – " + prettyDate(d) } ?: "") }).joinToString(" · "),
                            leading = { IconBadge(Icons.Outlined.Quiz, Color(0xFFF4511E)) },
                            trailing = {
                                if (user.isAdmin) TextButton(onClick = { vm.publish(e) }) { Text(if (e.isPublished) "Unpublish" else "Publish") }
                                else if (e.isPublished) StatusChip("present", "Results out")
                            },
                            onClick = { vm.open(e, child) })
                    }
                }
            }
        }
    }
    vm.selected?.let { e ->
        AlertDialog(onDismissRequest = { vm.selected = null }, title = { Text("${e.name} – date sheet") }, text = {
            Column(Modifier.verticalScrollSafe()) {
                if (staffSide && vm.classId == null) Text("Pick a class above to see its date sheet.")
                else if (vm.schedule.value.isEmpty()) Text("No papers scheduled yet.")
                vm.schedule.value.forEach { s ->
                    ListItem(headlineContent = { Text(s.subjectName ?: "") }, supportingContent = { Text("${prettyDate(s.examDate)} · ${s.startTime ?: ""}–${s.endTime ?: ""} · Max ${s.maxMarks}") })
                }
            }
        }, confirmButton = { TextButton(onClick = { vm.selected = null }) { Text("Close") } })
    }
    if (adding) {
        var name by remember { mutableStateOf("") }
        var term by remember { mutableStateOf("Term 1") }
        var start by remember { mutableStateOf("") }
        var end by remember { mutableStateOf("") }
        FormDialog("New exam", busy = vm.busy, onDismiss = { adding = false }, onConfirm = { vm.create(ExamCreate(name, term, start.ifBlank { null }, end.ifBlank { null })) { adding = false } }) {
            FormField("Exam name", name, { name = it })
            Dropdown("Term", listOf("Term 1" to "Term 1", "Term 2" to "Term 2"), term, { term = it })
            DateField("Starts", start, { start = it }); DateField("Ends", end, { end = it })
        }
    }
}

// ================================ Marks entry (admin / teacher) =================================

class MarksViewModel(c: AppContainer) : BaseViewModel(c) {
    val exams = mutableStateOf<List<Exam>>(emptyList())
    val subjects = mutableStateOf<List<Subject>>(emptyList())
    val sheet = mutableStateOf<Load<MarksSheet>?>(null)
    val values: SnapshotStateMap<Int, String> = mutableStateMapOf()
    val absent: SnapshotStateMap<Int, Boolean> = mutableStateMapOf()
    var examId by mutableStateOf<Int?>(null)
    var classId by mutableStateOf<Int?>(null)
    var sectionId by mutableStateOf<Int?>(null)
    var subjectId by mutableStateOf<Int?>(null)
    var maxMarks by mutableStateOf("100")

    init { act { exams.value = api.exams().data; null } }
    fun onClass(cId: Int?) = act { subjects.value = if (cId == null) emptyList() else api.subjects(cId).data; subjectId = null; sheet.value = null; null }
    fun loadSheet() {
        val e = examId; val s = sectionId; val sub = subjectId
        if (e == null || s == null || sub == null) return
        sheet.value = Load.Loading
        viewModelScope.launch {
            sheet.value = try {
                val r = api.marksSheet(e, s, sub).data!!
                values.clear(); absent.clear()
                r.rows.forEach { row -> values[row.studentId] = row.marksObtained?.let { if (it % 1.0 == 0.0) it.toInt().toString() else it.toString() } ?: ""; absent[row.studentId] = row.isAbsent }
                maxMarks = r.maxMarks.toString()
                Load.Ok(r)
            } catch (x: CancellationException) { throw x } catch (x: Throwable) { Load.Err(c.errorMessage(x)) }
        }
    }
    fun save() {
        val e = examId ?: return; val s = sectionId ?: return; val sub = subjectId ?: return
        val max = maxMarks.toIntOrNull() ?: return run { message = "Enter maximum marks" }
        val recs = values.mapNotNull { (id, v) ->
            val ab = absent[id] == true
            val m = v.toDoubleOrNull()
            if (!ab && m == null) null else MarkEntry(id, if (ab) null else m, ab)
        }
        if (recs.isEmpty()) { message = "Enter at least one student's marks"; return }
        act { api.saveMarks(MarksSubmit(e, s, sub, max, recs)).message }
    }
}

@Composable
fun MarksEntryScreen() {
    val scope = screenViewModel { ClassScope(it) }
    val vm = screenViewModel { MarksViewModel(it) }
    MessageEffect(vm)
    Scaffold(floatingActionButton = { if (vm.sheet.value is Load.Ok) ExtendedFloatingActionButton(onClick = vm::save, icon = { Icon(Icons.Outlined.Save, null) }, text = { Text("Save marks") }) }) { pad ->
        Column(Modifier.padding(pad)) {
            Column(Modifier.padding(horizontal = 16.dp, vertical = 8.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                Dropdown("Exam", vm.exams.value.map { it.id to it.name }, vm.examId, { vm.examId = it; vm.loadSheet() })
                ClassSectionPicker(scope.classes.value, vm.classId, vm.sectionId) { c, s -> if (c != vm.classId) vm.onClass(c); vm.classId = c; vm.sectionId = s; vm.loadSheet() }
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    Dropdown("Subject", vm.subjects.value.map { it.id to it.name }, vm.subjectId, { vm.subjectId = it; vm.loadSheet() }, Modifier.weight(1f))
                    FormField("Max", vm.maxMarks, { vm.maxMarks = it }, Modifier.width(90.dp), number = true)
                }
            }
            when (val s = vm.sheet.value) {
                null -> EmptyState("Pick exam, class, section and subject", Icons.Outlined.EditNote)
                is Load.Loading -> Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) { CircularProgressIndicator() }
                is Load.Err -> ErrorBox(s.message, vm::loadSheet)
                is Load.Ok -> ScreenList {
                    items(s.data.rows, key = { it.studentId }) { r ->
                        Card {
                            Row(Modifier.padding(horizontal = 12.dp, vertical = 6.dp), verticalAlignment = Alignment.CenterVertically) {
                                Text("${r.rollNo ?: ""}", Modifier.width(28.dp), style = MaterialTheme.typography.labelLarge)
                                Text(r.name ?: "", Modifier.weight(1f), maxLines = 1)
                                FilterChip(selected = vm.absent[r.studentId] == true, onClick = { vm.absent[r.studentId] = vm.absent[r.studentId] != true }, label = { Text("AB") })
                                Spacer(Modifier.width(6.dp))
                                OutlinedTextField(vm.values[r.studentId] ?: "", { vm.values[r.studentId] = it.filter { ch -> ch.isDigit() || ch == '.' } }, Modifier.width(84.dp),
                                    singleLine = true, enabled = vm.absent[r.studentId] != true, textStyle = MaterialTheme.typography.bodyLarge.copy(textAlign = TextAlign.Center),
                                    keyboardOptions = androidx.compose.foundation.text.KeyboardOptions(keyboardType = androidx.compose.ui.text.input.KeyboardType.Decimal))
                            }
                        }
                    }
                }
            }
        }
    }
}

// ================================ Results / report card ========================================

class ResultsViewModel(c: AppContainer) : BaseViewModel(c) {
    val exams = mutableStateOf<List<Exam>>(emptyList())
    val report = mutableStateOf<Load<ReportCard>?>(null)
    val students = mutableStateOf<List<Student>>(emptyList())
    var examId by mutableStateOf<Int?>(null)
    var classId by mutableStateOf<Int?>(null)
    var sectionId by mutableStateOf<Int?>(null)
    var studentId by mutableStateOf<Int?>(null)

    fun init(own: Boolean, childId: Int?) = act {
        exams.value = api.exams().data
        if (own) { examId = examId ?: exams.value.firstOrNull { it.isPublished }?.id; fetch(true, childId) }
        null
    }
    fun loadStudents() = act { students.value = sectionId?.let { api.students(sectionId = it).data } ?: emptyList(); null }
    fun fetch(own: Boolean, childId: Int?) {
        if (!own && studentId == null) return
        report.value = Load.Loading
        viewModelScope.launch {
            report.value = try {
                Load.Ok(if (own) api.myReportCard(examId, childId).data!! else api.reportCard(studentId!!, examId).data!!)
            } catch (x: CancellationException) { throw x } catch (x: Throwable) { Load.Err(c.errorMessage(x)) }
        }
    }
}

@Composable
fun ResultsScreen() {
    val vm = screenViewModel { ResultsViewModel(it) }
    val user = LocalUser.current
    val child = LocalChild.current.value
    val own = user.isParent || user.isStudent
    val scope = if (!own) screenViewModel { ClassScope(it) } else null
    LaunchedEffect(child) { vm.init(own, child) }
    Column {
        Column(Modifier.padding(horizontal = 16.dp, vertical = 8.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Dropdown("Exam", vm.exams.value.map { it.id to it.name + if (!it.isPublished && own) " (not published)" else "" }, vm.examId, { vm.examId = it; vm.fetch(own, child) })
            if (!own && scope != null) {
                ClassSectionPicker(scope.classes.value, vm.classId, vm.sectionId) { c, s -> vm.classId = c; vm.sectionId = s; vm.studentId = null; vm.loadStudents() }
                Dropdown("Student", vm.students.value.map { it.id to "${it.rollNo ?: ""}. ${it.displayName}" }, vm.studentId, { vm.studentId = it; vm.fetch(false, null) })
            }
        }
        when (val r = vm.report.value) {
            null -> EmptyState(if (own) "No results yet" else "Pick a student to see the report card", Icons.Outlined.MilitaryTech)
            is Load.Loading -> Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) { CircularProgressIndicator() }
            is Load.Err -> EmptyState(r.message, Icons.Outlined.HourglassEmpty)
            is Load.Ok -> ReportCardView(r.data)
        }
    }
}

@Composable
fun ReportCardView(rc: ReportCard) {
    ScreenList {
        item {
            HeroHeader(rc.student.displayName, "${rc.exam.name} · ${rc.student.classLabel ?: ""}", MaterialTheme.colorScheme.primary) {
                Spacer(Modifier.height(12.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(16.dp)) {
                    listOf("Percentage" to "${rc.percentage}%", "Grade" to (rc.grade ?: "-"), "Rank" to (rc.rank?.let { "$it / ${rc.classStrength}" } ?: "-"), "Result" to (rc.result ?: "-")).forEach { (l, v) ->
                        Column { Text(v, color = Color.White, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleMedium); Text(l, color = Color.White.copy(alpha = 0.8f), style = MaterialTheme.typography.labelSmall) }
                    }
                }
            }
        }
        item {
            Card {
                Column(Modifier.padding(12.dp)) {
                    Row(Modifier.padding(vertical = 6.dp)) {
                        Text("Subject", Modifier.weight(1f), style = MaterialTheme.typography.labelLarge)
                        Text("Marks", Modifier.width(80.dp), style = MaterialTheme.typography.labelLarge, textAlign = TextAlign.End)
                        Text("Grade", Modifier.width(56.dp), style = MaterialTheme.typography.labelLarge, textAlign = TextAlign.End)
                    }
                    HorizontalDivider()
                    rc.subjects.forEach { s ->
                        Row(Modifier.padding(vertical = 8.dp), verticalAlignment = Alignment.CenterVertically) {
                            Column(Modifier.weight(1f)) {
                                Text(s.subject ?: "")
                                LinearProgressIndicator(progress = { if (s.maxMarks > 0) ((s.marksObtained ?: 0.0) / s.maxMarks).toFloat() else 0f }, Modifier.fillMaxWidth(0.8f).padding(top = 4.dp))
                            }
                            Text(if (s.isAbsent) "AB" else "${(s.marksObtained ?: 0.0).let { if (it % 1.0 == 0.0) it.toInt() else it }}/${s.maxMarks}", Modifier.width(80.dp), textAlign = TextAlign.End)
                            Text(s.grade ?: "-", Modifier.width(56.dp), textAlign = TextAlign.End, fontWeight = FontWeight.Bold)
                        }
                    }
                    HorizontalDivider()
                    Row(Modifier.padding(vertical = 8.dp)) {
                        Text("Total", Modifier.weight(1f), fontWeight = FontWeight.Bold)
                        Text("${rc.total.toInt()}/${rc.maxTotal.toInt()}", fontWeight = FontWeight.Bold)
                    }
                    rc.gpa?.let { Text("Grade point average: $it", style = MaterialTheme.typography.bodySmall) }
                }
            }
        }
    }
}
