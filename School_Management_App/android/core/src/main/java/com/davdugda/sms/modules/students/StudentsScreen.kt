package com.davdugda.sms.modules.students

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.verticalScroll
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

// ================================ Students ======================================================

class StudentsViewModel(c: AppContainer) : BaseViewModel(c) {
    val list = loadState<List<Student>>()
    val classes = loadState<List<SchoolClass>>()
    val routes = mutableStateOf<List<TransportRoute>>(emptyList())
    val detail = mutableStateOf<Student?>(null)
    var classId by mutableStateOf<Int?>(null)
    var sectionId by mutableStateOf<Int?>(null)
    var search by mutableStateOf("")

    init { load(classes) { api.classes().data }; refresh() }
    fun refresh() = load(list, silent = true) { api.students(classId, sectionId, search.ifBlank { null }).data }
    fun open(s: Student) = act { detail.value = api.student(s.id).data; null }
    fun loadRoutes() = act { routes.value = api.routes().data; null }
    fun admit(body: StudentCreate, done: () -> Unit) = act(onDone = { done(); refresh() }) { api.createStudent(body).message }
    fun remove(s: Student) = act(onDone = { detail.value = null; refresh() }) { api.deleteStudent(s.id).message }
}

@Composable
fun StudentsScreen() {
    val vm = screenViewModel { StudentsViewModel(it) }
    MessageEffect(vm)
    val user = LocalUser.current
    var adding by remember { mutableStateOf(false) }
    val classes = (vm.classes.value as? Load.Ok)?.data ?: emptyList()

    Scaffold(
        floatingActionButton = {
            if (user.isAdmin) ExtendedFloatingActionButton(onClick = { adding = true; vm.loadRoutes() }, icon = { Icon(Icons.Outlined.PersonAdd, null) }, text = { Text("Admit student") })
        },
    ) { pad ->
        Column(Modifier.padding(pad)) {
            Column(Modifier.padding(horizontal = 16.dp, vertical = 8.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                SearchField(vm.search, { vm.search = it; vm.refresh() }, "Search name, admission no. or phone")
                ClassSectionPicker(classes, vm.classId, vm.sectionId, allowAll = true) { cId, sId -> vm.classId = cId; vm.sectionId = sId; vm.refresh() }
            }
            LoadContent(vm.list.value, onRetry = vm::refresh) { list ->
                ScreenList {
                    item { Text("${list.size} students", style = MaterialTheme.typography.labelLarge, color = MaterialTheme.colorScheme.onSurfaceVariant) }
                    if (list.isEmpty()) item { EmptyState("No students found") }
                    items(list, key = { it.id }) { s ->
                        InfoCard(
                            s.displayName,
                            subtitle = "${s.classLabel ?: ""} · Roll ${s.rollNo ?: "-"}",
                            supporting = "Adm. ${s.admissionNo}" + (s.parent?.let { " · Parent: ${it.name} ${it.phone ?: ""}" } ?: ""),
                            leading = { Avatar(s.displayName) },
                            onClick = { vm.open(s) },
                        )
                    }
                }
            }
        }
    }
    vm.detail.value?.let { s -> StudentDetailDialog(s, canDelete = user.isAdmin, onDelete = { vm.remove(s) }, onDismiss = { vm.detail.value = null }) }
    if (adding) AdmitStudentDialog(classes, vm.routes.value, vm.busy, onDismiss = { adding = false }) { vm.admit(it) { adding = false } }
}

@Composable
fun StudentDetailDialog(s: Student, canDelete: Boolean, onDelete: () -> Unit, onDismiss: () -> Unit) {
    var confirm by remember { mutableStateOf(false) }
    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text(s.displayName) },
        text = {
            Column(Modifier.verticalScrollSafe()) {
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    s.attendanceSummary?.let { StatusChip("present", "Attendance ${it.percentage}%") }
                    s.feeSummary?.let { StatusChip(if (it.due > 0) "unpaid" else "paid", if (it.due > 0) "Due ${rupees(it.due)}" else "Fees clear") }
                }
                Spacer(Modifier.height(8.dp))
                LabeledValue("Class", s.classLabel)
                LabeledValue("Roll no.", s.rollNo?.toString())
                LabeledValue("Admission no.", s.admissionNo)
                LabeledValue("Admitted on", s.admissionDate?.let { prettyDate(it) })
                LabeledValue("Father", s.fatherName)
                LabeledValue("Mother", s.motherName)
                LabeledValue("Parent login", s.parent?.let { "${it.name} · ${it.phone ?: it.email ?: ""}" })
                LabeledValue("Guardian phone", s.guardianPhone)
                LabeledValue("Blood group", s.bloodGroup)
                LabeledValue("Category", s.category)
                LabeledValue("House", s.house)
                LabeledValue("Bus route", s.transportRoute?.name)
                LabeledValue("Student login", s.user?.email ?: s.user?.phone)
            }
        },
        confirmButton = { TextButton(onClick = onDismiss) { Text("Close") } },
        dismissButton = { if (canDelete) TextButton(onClick = { confirm = true }) { Text("Remove", color = MaterialTheme.colorScheme.error) } },
    )
    if (confirm) ConfirmDialog("Remove student?", "This deletes ${s.displayName}'s login and records.", onDismiss = { confirm = false }, onConfirm = onDelete)
}

@Composable
private fun AdmitStudentDialog(classes: List<SchoolClass>, routes: List<TransportRoute>, busy: Boolean, onDismiss: () -> Unit, onSave: (StudentCreate) -> Unit) {
    var name by remember { mutableStateOf("") }
    var classId by remember { mutableStateOf<Int?>(null) }
    var sectionId by remember { mutableStateOf<Int?>(null) }
    var gender by remember { mutableStateOf("male") }
    var dob by remember { mutableStateOf("") }
    var father by remember { mutableStateOf("") }
    var mother by remember { mutableStateOf("") }
    var parentPhone by remember { mutableStateOf("") }
    var address by remember { mutableStateOf("") }
    var blood by remember { mutableStateOf("") }
    var route by remember { mutableStateOf<Int?>(null) }
    var err by remember { mutableStateOf<String?>(null) }
    FormDialog("Admit a new student", confirmText = "Admit", busy = busy, onDismiss = onDismiss, onConfirm = {
        if (name.isBlank() || classId == null || sectionId == null) err = "Name, class and section are required"
        else onSave(StudentCreate(name.trim(), classId!!, sectionId!!, gender, dob.ifBlank { null }, null, null, father.ifBlank { null }, mother.ifBlank { null },
            father.ifBlank { null }, parentPhone.ifBlank { null }, address.ifBlank { null }, blood.ifBlank { null }, route))
    }) {
        FormField("Student's full name *", name, { name = it })
        ClassSectionPicker(classes, classId, sectionId) { c, s -> classId = c; sectionId = s }
        Dropdown("Gender", listOf("male" to "Male", "female" to "Female", "other" to "Other"), gender, { gender = it })
        DateField("Date of birth", dob, { dob = it })
        FormField("Father's name", father, { father = it })
        FormField("Mother's name", mother, { mother = it })
        FormField("Parent mobile (parent app login)", parentPhone, { parentPhone = it }, number = true)
        FormField("Address", address, { address = it })
        FormField("Blood group", blood, { blood = it })
        if (routes.isNotEmpty()) Dropdown("Bus route", listOf<Int?>(null).map { it to "No transport" } + routes.map { it.id to it.name }, route, { route = it })
        Text("A student login is created automatically (password student123), and the parent login uses the mobile number (password parent123).",
            style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
        err?.let { Text(it, color = MaterialTheme.colorScheme.error) }
    }
}

/** Class + section dropdowns used by many screens. */
@Composable
fun ClassSectionPicker(classes: List<SchoolClass>, classId: Int?, sectionId: Int?, allowAll: Boolean = false, onChange: (Int?, Int?) -> Unit) {
    val cls = classes.firstOrNull { it.id == classId }
    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
        val classOpts: List<Pair<Int?, String>> = (if (allowAll) listOf<Pair<Int?, String>>(null to "All classes") else emptyList()) + classes.map { it.id to classDisplayName(it.name) }
        Dropdown("Class", classOpts, classId, { c -> onChange(c, classes.firstOrNull { it.id == c }?.sections?.singleOrNull()?.id) }, Modifier.weight(1f))
        // Sections always say their class ("Class 6 – A"); until a class is picked there is nothing to choose.
        val secOpts: List<Pair<Int?, String>> = (if (allowAll) listOf<Pair<Int?, String>>(null to (cls?.let { "All of ${classDisplayName(it.name)}" } ?: "All sections")) else emptyList()) +
            (cls?.sections ?: emptyList()).map { it.id to it.labelIn(cls) }
        Dropdown(if (cls == null && !allowAll) "Section (choose class first)" else "Section", secOpts, sectionId, { s -> onChange(classId, s) }, Modifier.weight(1f))
    }
}

