package com.davdugda.sms.modules.transport

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

// ================================ Transport ====================================================

class TransportViewModel(c: AppContainer) : BaseViewModel(c) {
    val list = loadState<List<TransportRoute>>()
    val riders = mutableStateOf<List<Student>?>(null)
    init { refresh() }
    fun refresh() = load(list, silent = true) { api.routes().data }
    fun loadRiders(r: TransportRoute) = act { riders.value = api.routeStudents(r.id).data; null }
    fun add(body: RouteCreate, done: () -> Unit) = act(onDone = { done(); refresh() }) { api.createRoute(body).message }
}

@Composable
fun TransportScreen() {
    val vm = screenViewModel { TransportViewModel(it) }
    MessageEffect(vm)
    val user = LocalUser.current
    val staffSide = user.isAdmin || user.isStaffMember
    var adding by remember { mutableStateOf(false) }
    var open by remember { mutableStateOf<TransportRoute?>(null) }
    Scaffold(floatingActionButton = { if (user.isAdmin) ExtendedFloatingActionButton(onClick = { adding = true }, icon = { Icon(Icons.Outlined.AddRoad, null) }, text = { Text("Add route") }) }) { pad ->
        Box(Modifier.padding(pad)) {
            LoadContent(vm.list.value, vm::refresh) { list ->
                val sorted = list.sortedByDescending { it.isMine }
                ScreenList {
                    if (list.isEmpty()) item { EmptyState("No bus routes") }
                    items(sorted, key = { it.id }) { r ->
                        ElevatedCard(onClick = { open = r; if (staffSide) vm.loadRiders(r) }, modifier = Modifier.fillMaxWidth(),
                            colors = CardDefaults.elevatedCardColors(containerColor = if (r.isMine) MaterialTheme.colorScheme.primaryContainer else MaterialTheme.colorScheme.surface)) {
                            Column(Modifier.padding(14.dp)) {
                                Row {
                                    IconBadge(Icons.Outlined.DirectionsBus, Color(0xFFFFB300))
                                    Spacer(Modifier.width(12.dp))
                                    Column(Modifier.weight(1f)) {
                                        Text(r.name, style = MaterialTheme.typography.titleMedium)
                                        Text("${r.vehicleNo ?: ""} · ${rupees(r.fare)}/month", style = MaterialTheme.typography.bodySmall)
                                    }
                                    if (r.isMine) StatusChip("present", "Your bus")
                                }
                                Spacer(Modifier.height(8.dp))
                                Text("Driver: ${r.driverName ?: "-"} · ${r.driverPhone ?: ""}", style = MaterialTheme.typography.bodyMedium)
                                Text("Pickup ${r.pickupTime ?: "-"} · Drop ${r.dropTime ?: "-"}", style = MaterialTheme.typography.bodyMedium)
                                r.stops?.takeIf { it.isNotEmpty() }?.let { Text("Stops: " + it.joinToString(" → "), style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant) }
                                if (staffSide) Text("${r.studentsCount} students", style = MaterialTheme.typography.labelMedium)
                            }
                        }
                    }
                }
            }
        }
    }
    open?.takeIf { staffSide }?.let { r ->
        AlertDialog(onDismissRequest = { open = null; vm.riders.value = null }, title = { Text(r.name) }, text = {
            Column(Modifier.verticalScrollSafe()) {
                val rs = vm.riders.value
                if (rs == null) CircularProgressIndicator() else if (rs.isEmpty()) Text("No students on this route")
                rs?.forEach { s -> Text("• ${s.displayName} (${s.classLabel ?: ""})") }
            }
        }, confirmButton = { TextButton(onClick = { open = null; vm.riders.value = null }) { Text("Close") } })
    }
    if (adding) {
        var name by remember { mutableStateOf("") }; var vehicle by remember { mutableStateOf("") }; var driver by remember { mutableStateOf("") }
        var phone by remember { mutableStateOf("") }; var fare by remember { mutableStateOf("") }; var stops by remember { mutableStateOf("") }
        FormDialog("Add bus route", busy = vm.busy, onDismiss = { adding = false }, onConfirm = {
            vm.add(RouteCreate(name, vehicle.ifBlank { null }, driver.ifBlank { null }, phone.ifBlank { null }, fare.toDoubleOrNull() ?: 0.0, stops.split(",").map { it.trim() }.filter { it.isNotEmpty() })) { adding = false }
        }) {
            FormField("Route name", name, { name = it }); FormField("Vehicle no.", vehicle, { vehicle = it }); FormField("Driver", driver, { driver = it })
            FormField("Driver phone", phone, { phone = it }, number = true); FormField("Monthly fare (₹)", fare, { fare = it }, number = true)
            FormField("Stops, comma separated", stops, { stops = it })
        }
    }
}

