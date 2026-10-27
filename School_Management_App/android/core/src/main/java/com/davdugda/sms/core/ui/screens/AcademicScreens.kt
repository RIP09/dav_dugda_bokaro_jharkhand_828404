package com.davdugda.sms.core.ui.screens

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import com.davdugda.sms.core.data.*
import com.davdugda.sms.core.ui.*
import com.davdugda.sms.core.ui.components.*

// ================================ Classes, sections, subjects ======================================

class ClassesViewModel(c: AppContainer) : BaseViewModel(c) {
    val list = loadState<List<SchoolClass>>()
    val teachers = mutableStateOf<List<User>>(emptyList())
    init { refresh(); act { teachers.value = api.users("teacher").data; null } }
    fun refresh() = load(list, silent = true) { api.classes().data }
    fun addClass(name: String, sections: List<String>, done: () -> Unit) = act(onDone = { done(); refresh() }) { api.createClass(ClassCreate(name, sections)).message }
    fun addSection(classId: Int, name: String, teacherId: Int?, done: () -> Unit) = act(onDone = { done(); refresh() }) { api.createSection(SectionCreate(classId, name, teacherId)).message }
    fun addSubject(classId: Int, name: String, code: String?, teacherId: Int?, done: () -> Unit) = act(onDone = { done(); refresh() }) { api.createSubject(SubjectCreate(classId, name, code, teacherId)).message }
    fun deleteSubject(id: Int) = act(onDone = ::refresh) { api.deleteSubject(id).message }
    fun deleteClass(id: Int) = act(onDone = ::refresh) { api.deleteClass(id).message }
}

@Composable
fun ClassesScreen() {
    val vm = screenViewModel { ClassesViewModel(it) }
    MessageEffect(vm)
    var addClass by remember { mutableStateOf(false) }
    var addSectionFor by remember { mutableStateOf<SchoolClass?>(null) }
    var addSubjectFor by remember { mutableStateOf<SchoolClass?>(null) }
    var expanded by remember { mutableStateOf<Int?>(null) }
    val teacherOpts = listOf<Pair<Int?, String>>(null to "Not assigned") + vm.teachers.value.map { it.id to it.name }

    Scaffold(floatingActionButton = { ExtendedFloatingActionButton(onClick = { addClass = true }, icon = { Icon(Icons.Outlined.Add, null) }, text = { Text("New class") }) }) { pad ->
        Box(Modifier.padding(pad)) {
            LoadContent(vm.list.value, onRetry = vm::refresh) { list ->
                ScreenList {
                    items(list, key = { it.id }) { cl ->
                        val open = expanded == cl.id
                        ElevatedCard(onClick = { expanded = if (open) null else cl.id }, modifier = Modifier.fillMaxWidth()) {
                            Column(Modifier.padding(14.dp)) {
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    IconBadge(Icons.Outlined.Class, MaterialTheme.colorScheme.primary)
                                    Spacer(Modifier.width(12.dp))
                                    Column(Modifier.weight(1f)) {
                                        Text(cl.name, style = MaterialTheme.typography.titleMedium)
                                        Text("${cl.studentsCount} students · ${cl.sections.size} section(s) · ${cl.subjects.size} subjects", style = MaterialTheme.typography.bodySmall)
                                    }
                                    Icon(if (open) Icons.Outlined.ExpandLess else Icons.Outlined.ExpandMore, null)
                                }
                                if (open) {
                                    Spacer(Modifier.height(10.dp))
                                    Text("Sections", style = MaterialTheme.typography.labelLarge)
                                    cl.sections.forEach { s ->
                                        Text("• ${s.name} — class teacher: ${s.teacher?.name ?: "not set"}${s.room?.let { " · $it" } ?: ""}", style = MaterialTheme.typography.bodyMedium)
                                    }
                                    TextButton(onClick = { addSectionFor = cl }) { Icon(Icons.Outlined.Add, null); Text("Add section") }
                                    Text("Subjects", style = MaterialTheme.typography.labelLarge)
                                    cl.subjects.forEach { sub ->
                                        Row(verticalAlignment = Alignment.CenterVertically) {
                                            Text("• ${sub.name}${sub.code?.let { " ($it)" } ?: ""} — ${sub.teacher?.name ?: "no teacher"}", Modifier.weight(1f), style = MaterialTheme.typography.bodyMedium)
                                            IconButton(onClick = { vm.deleteSubject(sub.id) }) { Icon(Icons.Outlined.DeleteOutline, "Delete", Modifier.size(18.dp)) }
                                        }
                                    }
                                    Row {
                                        TextButton(onClick = { addSubjectFor = cl }) { Icon(Icons.Outlined.Add, null); Text("Add subject") }
                                        Spacer(Modifier.weight(1f))
                                        if (cl.studentsCount == 0) TextButton(onClick = { vm.deleteClass(cl.id) }) { Text("Delete class", color = MaterialTheme.colorScheme.error) }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    if (addClass) {
        var name by remember { mutableStateOf("") }
        var secs by remember { mutableStateOf("A") }
        FormDialog("New class", busy = vm.busy, onDismiss = { addClass = false }, onConfirm = {
            vm.addClass(name.trim(), secs.split(",").map { it.trim() }.filter { it.isNotEmpty() }) { addClass = false }
        }) {
            FormField("Class name (e.g. Class 6, Nursery)", name, { name = it })
            FormField("Sections, comma separated", secs, { secs = it })
        }
    }
    addSectionFor?.let { cl ->
        var name by remember { mutableStateOf("") }
        var teacher by remember { mutableStateOf<Int?>(null) }
        FormDialog("Add section to ${cl.name}", busy = vm.busy, onDismiss = { addSectionFor = null }, onConfirm = { vm.addSection(cl.id, name.trim(), teacher) { addSectionFor = null } }) {
            FormField("Section name", name, { name = it })
            Dropdown("Class teacher", teacherOpts, teacher, { teacher = it })
        }
    }
    addSubjectFor?.let { cl ->
        var name by remember { mutableStateOf("") }
        var code by remember { mutableStateOf("") }
        var teacher by remember { mutableStateOf<Int?>(null) }
        FormDialog("Add subject to ${cl.name}", busy = vm.busy, onDismiss = { addSubjectFor = null }, onConfirm = { vm.addSubject(cl.id, name.trim(), code.ifBlank { null }, teacher) { addSubjectFor = null } }) {
            FormField("Subject name", name, { name = it })
            FormField("Code", code, { code = it })
            Dropdown("Subject teacher", teacherOpts, teacher, { teacher = it })
        }
    }
}

// ================================ School settings (admin) ===========================================

class SettingsViewModel(c: AppContainer) : BaseViewModel(c) {
    val school = loadState<Map<String, String?>>()
    val years = loadState<List<AcademicYear>>()
    val grades = loadState<List<Grade>>()
    val feeTypes = loadState<List<FeeType>>()
    init { refresh() }
    fun refresh() {
        load(school) { api.school().data ?: emptyMap() }
        load(years) { api.academicYears().data }
        load(grades) { api.grades().data }
        load(feeTypes) { api.feeTypes().data }
    }
    fun addFeeType(name: String, amount: Double, done: () -> Unit) = act(onDone = { done(); refresh() }) { api.createFeeType(FeeTypeCreate(name, null, amount)).message }
}

@Composable
fun SettingsScreen() {
    val vm = screenViewModel { SettingsViewModel(it) }
    MessageEffect(vm)
    var addFee by remember { mutableStateOf(false) }
    ScreenList {
        item { SectionHeader("School profile") }
        item {
            LoadContent(vm.school.value, vm::refresh) { s ->
                Card { Column(Modifier.fillMaxWidth().padding(16.dp)) {
                    LabeledValue("Name", s["school_name"]); LabeledValue("Address", s["address"]); LabeledValue("Affiliation", s["affiliation"])
                    LabeledValue("Principal", s["principal"]); LabeledValue("Phone", s["phone"]); LabeledValue("Email", s["email"])
                    LabeledValue("Current session", s["current_session"]); LabeledValue("Library fine/day", s["library_fine_per_day"]?.let { "₹$it" })
                } }
            }
        }
        item { SectionHeader("Academic sessions") }
        when (val y = vm.years.value) { is Load.Ok -> items(y.data) { yr -> InfoCard(yr.title, subtitle = "${prettyDate(yr.startDate)} – ${prettyDate(yr.endDate)}", trailing = { if (yr.isCurrent) StatusChip("present", "Current") }) }; else -> {} }
        item { SectionHeader("Grade scale (CBSE)") }
        when (val g = vm.grades.value) { is Load.Ok -> items(g.data) { gr -> InfoCard("${gr.name}  ·  ${gr.minPercent.toInt()}–${gr.maxPercent.toInt()}%", subtitle = "Grade point ${gr.point} · ${gr.remark ?: ""}") }; else -> {} }
        item { SectionHeader("Fee types") { TextButton(onClick = { addFee = true }) { Text("Add") } } }
        when (val f = vm.feeTypes.value) { is Load.Ok -> items(f.data) { ft -> InfoCard(ft.name, subtitle = ft.description, trailing = { Text(rupees(ft.defaultAmount)) }) }; else -> {} }
    }
    if (addFee) {
        var name by remember { mutableStateOf("") }
        var amount by remember { mutableStateOf("") }
        FormDialog("New fee type", busy = vm.busy, onDismiss = { addFee = false }, onConfirm = { vm.addFeeType(name, amount.toDoubleOrNull() ?: 0.0) { addFee = false } }) {
            FormField("Name", name, { name = it }); FormField("Default amount (₹)", amount, { amount = it }, number = true)
        }
    }
}
