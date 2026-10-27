package com.davdugda.sms.modules.profile

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.outlined.Logout
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import com.davdugda.sms.core.data.AppContainer
import com.davdugda.sms.core.data.ChangePassword
import com.davdugda.sms.core.data.User
import com.davdugda.sms.core.ui.*
import com.davdugda.sms.core.ui.components.*
import com.davdugda.sms.core.data.*
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

class ProfileViewModel(c: AppContainer) : BaseViewModel(c) {
    fun refresh(onUser: (User) -> Unit) = act { api.me().data?.let(onUser); null }
    fun changePassword(cur: String, new: String, done: () -> Unit) = act(onDone = done) { api.changePassword(ChangePassword(cur, new)).message }
    fun logout(done: () -> Unit) = act(onDone = { c.session.clear(); done() }) { runCatching { api.logout() }; "Signed out" }
}

@Composable
fun ProfileScreen(onUserUpdated: (User) -> Unit, onLogout: () -> Unit) {
    val vm = screenViewModel { ProfileViewModel(it) }
    MessageEffect(vm)
    val u = LocalUser.current
    val c = LocalContainer.current
    var pwDialog by remember { mutableStateOf(false) }
    var serverDialog by remember { mutableStateOf(false) }
    LaunchedEffect(Unit) { vm.refresh(onUserUpdated) }

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Card {
            Column(Modifier.fillMaxWidth().padding(20.dp), horizontalAlignment = Alignment.CenterHorizontally) {
                Avatar(u.initials, MaterialTheme.colorScheme.primary, 84)
                Spacer(Modifier.height(10.dp))
                Text(u.name, style = MaterialTheme.typography.titleLarge)
                Text(u.staff?.designation ?: u.roleLabel, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
        }
        Card {
            Column(Modifier.fillMaxWidth().padding(16.dp)) {
                SectionHeader("Details")
                LabeledValue("Email", u.email)
                LabeledValue("Phone", u.phone)
                LabeledValue("Gender", u.gender?.cap())
                LabeledValue("Date of birth", u.dob?.let { prettyDate(it) })
                LabeledValue("Address", u.address)
                u.staff?.let { s ->
                    LabeledValue("Employee ID", s.employeeId)
                    LabeledValue("Department", s.department)
                    LabeledValue("Qualification", s.qualification)
                    LabeledValue("Joined", s.joiningDate?.let { prettyDate(it) })
                }
                u.student?.let { s ->
                    LabeledValue("Admission no.", s.admissionNo)
                    LabeledValue("Class", s.classLabel)
                    LabeledValue("Roll no.", s.rollNo?.toString())
                    LabeledValue("House", s.house)
                    LabeledValue("Blood group", s.bloodGroup)
                    LabeledValue("Father", s.fatherName)
                    LabeledValue("Mother", s.motherName)
                    LabeledValue("Bus route", s.transportRoute?.name)
                }
            }
        }
        if (u.children.isNotEmpty()) {
            Card {
                Column(Modifier.fillMaxWidth().padding(16.dp)) {
                    SectionHeader("My children")
                    u.children.forEach { s ->
                        ListItem(headlineContent = { Text(s.displayName) }, supportingContent = { Text("${s.classLabel ?: ""} · Adm. ${s.admissionNo}") },
                            leadingContent = { Avatar(s.displayName, MaterialTheme.colorScheme.secondary, 38) })
                    }
                }
            }
        }
        Card {
            Column(Modifier.fillMaxWidth()) {
                ListItem(headlineContent = { Text("Change password") }, leadingContent = { Icon(Icons.Outlined.Password, null) },
                    modifier = Modifier.clickableRow { pwDialog = true })
                ListItem(headlineContent = { Text("Server address") }, supportingContent = { Text(c.session.baseUrl) }, leadingContent = { Icon(Icons.Outlined.Dns, null) },
                    modifier = Modifier.clickableRow { serverDialog = true })
                ListItem(headlineContent = { Text("About") }, supportingContent = { Text("${c.config.appName} v${c.config.versionName} · DAV Public School, Dugda, Bokaro, Jharkhand 828404") },
                    leadingContent = { Icon(Icons.Outlined.Info, null) })
                ListItem(headlineContent = { Text("Sign out", color = MaterialTheme.colorScheme.error) }, leadingContent = { Icon(Icons.AutoMirrored.Outlined.Logout, null, tint = MaterialTheme.colorScheme.error) },
                    modifier = Modifier.clickableRow { vm.logout(onLogout) })
            }
        }
    }
    if (pwDialog) {
        var cur by remember { mutableStateOf("") }
        var new by remember { mutableStateOf("") }
        var again by remember { mutableStateOf("") }
        FormDialog("Change password", busy = vm.busy, onDismiss = { pwDialog = false }, onConfirm = {
            if (new.length < 6) vm.message = "New password must be at least 6 characters"
            else if (new != again) vm.message = "Passwords do not match"
            else vm.changePassword(cur, new) { pwDialog = false }
        }) {
            FormField("Current password", cur, { cur = it }, password = true)
            FormField("New password", new, { new = it }, password = true)
            FormField("Repeat new password", again, { again = it }, password = true)
        }
    }
    if (serverDialog) ServerDialog { serverDialog = false }
}

