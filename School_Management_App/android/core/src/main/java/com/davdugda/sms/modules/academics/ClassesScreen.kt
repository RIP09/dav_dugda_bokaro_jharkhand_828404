package com.davdugda.sms.modules.academics

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

