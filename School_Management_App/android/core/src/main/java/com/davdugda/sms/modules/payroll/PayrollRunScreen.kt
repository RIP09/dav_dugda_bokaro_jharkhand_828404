package com.davdugda.sms.modules.payroll

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextDecoration
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewModelScope
import com.davdugda.sms.core.data.*
import com.davdugda.sms.core.nav.*
import com.davdugda.sms.core.ui.*
import com.davdugda.sms.core.ui.components.*
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import kotlinx.serialization.json.*
import android.net.Uri
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import java.time.YearMonth

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


/** Makes this month's draft salary slips from the salary structures; pay each slip from Payslips. */
class PayrollRunViewModel(c: AppContainer) : BaseViewModel(c) {
    var month by mutableStateOf(YearMonth.now().toString())
    var result by mutableStateOf<JsonElement?>(null)
    fun run() = act { val r = api.payrollRun(buildJsonObject { put("month", month) }); result = r.data; r.message ?: "Draft salary slips created for $month." }
}

@Composable
fun PayrollRunScreen() {
    val vm = screenViewModel { PayrollRunViewModel(it) }
    val navigate = LocalNavigate.current
    MessageEffect(vm)
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        StepCard(1, "Pick the month", "Salary slips are made for every staff member who has a salary structure.") {
            Row(verticalAlignment = Alignment.CenterVertically) {
                IconButton(onClick = { vm.month = YearMonth.parse(vm.month).minusMonths(1).toString() }) { Icon(Icons.Outlined.ChevronLeft, "Previous") }
                Text(vm.month, style = MaterialTheme.typography.titleMedium)
                IconButton(onClick = { vm.month = YearMonth.parse(vm.month).plusMonths(1).toString() }) { Icon(Icons.Outlined.ChevronRight, "Next") }
            }
        }
        StepCard(2, "Create draft slips", "Drafts can still be edited (allowances, deductions). Once a slip is marked Paid it is locked.") {
            Button(onClick = { vm.run() }, enabled = !vm.busy) { Text("Run payroll for ${vm.month}") }
        }
        StepCard(3, "Review & pay", "Open Payslips, check each slip, then use “Pay”. Print the salary slip as PDF.") {
            OutlinedButton(onClick = { navigate(Routes.module("payslips")) }) { Text("Open payslips") }
        }
        if (vm.busy) LinearProgressIndicator(Modifier.fillMaxWidth())
        vm.result?.let { ElevatedCard { Column(Modifier.padding(14.dp)) { JsonResultView(it) } } }
    }
}
