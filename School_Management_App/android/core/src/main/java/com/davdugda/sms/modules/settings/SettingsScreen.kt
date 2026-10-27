package com.davdugda.sms.modules.settings

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
