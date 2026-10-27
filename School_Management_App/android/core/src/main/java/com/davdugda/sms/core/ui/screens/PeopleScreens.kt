package com.davdugda.sms.core.ui.screens

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
        val classOpts: List<Pair<Int?, String>> = (if (allowAll) listOf<Pair<Int?, String>>(null to "All classes") else emptyList()) + classes.map { it.id to it.name }
        Dropdown("Class", classOpts, classId, { c -> onChange(c, classes.firstOrNull { it.id == c }?.sections?.singleOrNull()?.id) }, Modifier.weight(1f))
        val secOpts: List<Pair<Int?, String>> = (if (allowAll) listOf<Pair<Int?, String>>(null to "All") else emptyList()) + (cls?.sections ?: emptyList()).map { it.id to it.name }
        Dropdown("Section", secOpts, sectionId, { s -> onChange(classId, s) }, Modifier.weight(0.7f))
    }
}

// ================================ Staff & parents ================================================

class UsersViewModel(c: AppContainer, private val role: String) : BaseViewModel(c) {
    val list = loadState<List<User>>()
    var search by mutableStateOf("")
    init { refresh() }
    fun refresh() = load(list, silent = true) { api.users(role, search.ifBlank { null }).data }
    fun create(body: UserCreate, done: () -> Unit) = act(onDone = { done(); refresh() }) { api.createUser(body).message }
    fun toggle(u: User) = act(onDone = ::refresh) { api.toggleUser(u.id).message }
}

@Composable
fun StaffScreen() = UsersScreen("staff_all", "Add staff", staffForm = true)

@Composable
fun ParentsScreen() = UsersScreen("parent", "Add parent", staffForm = false)

@Composable
private fun UsersScreen(role: String, addLabel: String, staffForm: Boolean) {
    val vm = screenViewModel(key = "users-$role") { UsersViewModel(it, role) }
    MessageEffect(vm)
    var adding by remember { mutableStateOf(false) }
    var selected by remember { mutableStateOf<User?>(null) }
    Scaffold(floatingActionButton = { ExtendedFloatingActionButton(onClick = { adding = true }, icon = { Icon(Icons.Outlined.PersonAdd, null) }, text = { Text(addLabel) }) }) { pad ->
        Column(Modifier.padding(pad)) {
            Box(Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) { SearchField(vm.search, { vm.search = it; vm.refresh() }, "Search name, phone or email") }
            LoadContent(vm.list.value, onRetry = vm::refresh) { list ->
                ScreenList {
                    item { Text("${list.size} people", style = MaterialTheme.typography.labelLarge, color = MaterialTheme.colorScheme.onSurfaceVariant) }
                    if (list.isEmpty()) item { EmptyState("Nobody here yet") }
                    items(list, key = { it.id }) { u ->
                        InfoCard(
                            u.name,
                            subtitle = if (staffForm) "${u.staff?.designation ?: u.roleLabel}${u.staff?.department?.let { " · $it" } ?: ""}" else u.children.joinToString { "${it.displayName} (${it.classLabel ?: ""})" }.ifBlank { "No child linked" },
                            supporting = listOfNotNull(u.phone, u.email).joinToString(" · "),
                            leading = { Avatar(u.initials, if (u.isActive) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.outline) },
                            trailing = { if (!u.isActive) StatusChip("absent", "Disabled") },
                            onClick = { selected = u },
                        )
                    }
                }
            }
        }
    }
    selected?.let { u ->
        AlertDialog(onDismissRequest = { selected = null }, title = { Text(u.name) }, text = {
            Column {
                LabeledValue("Role", u.roleLabel)
                LabeledValue("Employee ID", u.staff?.employeeId)
                LabeledValue("Designation", u.staff?.designation)
                LabeledValue("Department", u.staff?.department)
                LabeledValue("Qualification", u.staff?.qualification)
                LabeledValue("Joined", u.staff?.joiningDate?.let { prettyDate(it) })
                LabeledValue("Phone", u.phone)
                LabeledValue("Email", u.email)
                LabeledValue("Address", u.address)
                LabeledValue("Children", u.children.joinToString { it.displayName })
                LabeledValue("Last sign-in", u.lastLoginAt?.let { prettyDate(it) })
            }
        }, confirmButton = { TextButton(onClick = { selected = null }) { Text("Close") } },
            dismissButton = { TextButton(onClick = { vm.toggle(u); selected = null }) { Text(if (u.isActive) "Disable login" else "Enable login") } })
    }
    if (adding) {
        var name by remember { mutableStateOf("") }
        var phone by remember { mutableStateOf("") }
        var email by remember { mutableStateOf("") }
        var r by remember { mutableStateOf(if (staffForm) "teacher" else "parent") }
        var designation by remember { mutableStateOf("") }
        var dept by remember { mutableStateOf("") }
        var pw by remember { mutableStateOf("") }
        FormDialog(addLabel, busy = vm.busy, onDismiss = { adding = false }, onConfirm = {
            if (name.isBlank() || (phone.isBlank() && email.isBlank())) vm.message = "Name and a phone or email are required"
            else vm.create(UserCreate(name.trim(), email.ifBlank { null }, phone.ifBlank { null }, r, pw.ifBlank { null }, designation.ifBlank { null }, dept.ifBlank { null })) { adding = false }
        }) {
            FormField("Full name *", name, { name = it })
            FormField("Mobile", phone, { phone = it }, number = true)
            FormField("Email", email, { email = it })
            if (staffForm) {
                Dropdown("Role", listOf("teacher" to "Teacher", "accountant" to "Accountant", "librarian" to "Librarian", "staff" to "Other staff", "admin" to "Admin (School app)"), r, { r = it })
                FormField("Designation (e.g. PGT, TGT, Clerk)", designation, { designation = it })
                FormField("Department / subject", dept, { dept = it })
            }
            FormField("Password (blank = default)", pw, { pw = it }, password = true)
            Text(if (staffForm) "Default password: staff123. They sign in to the ${if (r == "admin") "School" else "Staff"} app." else "Default password: parent123. Link children when admitting students (parent mobile).",
                style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
    }
}

@Composable
fun Modifier.verticalScrollSafe(): Modifier = this.then(Modifier.verticalScroll(androidx.compose.foundation.rememberScrollState()))
