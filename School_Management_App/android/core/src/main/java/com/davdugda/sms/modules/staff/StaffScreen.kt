package com.davdugda.sms.modules.staff

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
internal fun UsersScreen(role: String, addLabel: String, staffForm: Boolean) {
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

