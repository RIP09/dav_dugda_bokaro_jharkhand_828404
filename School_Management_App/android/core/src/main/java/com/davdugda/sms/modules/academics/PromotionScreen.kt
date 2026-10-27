package com.davdugda.sms.modules.academics

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
import androidx.compose.material.icons.automirrored.outlined.ArrowForward

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


/** Session rollover: move students to the next class in the new academic year. */
class PromotionViewModel(c: AppContainer) : BaseViewModel(c) {
    val years = loadState<List<AcademicYear>>()
    val classes = loadState<List<SchoolClass>>()
    var fromYear by mutableStateOf<Int?>(null)
    var toYear by mutableStateOf<Int?>(null)
    val mapping = mutableStateMapOf<Int, Int?>()   // from class -> to class (null = pass out / leave)
    var result by mutableStateOf<JsonElement?>(null)
    var confirm by mutableStateOf(false)
    fun start() {
        load(years) { api.academicYears().data.also { ys -> fromYear = ys.firstOrNull { it.isCurrent }?.id ?: ys.firstOrNull()?.id; toYear = ys.firstOrNull { it.id != fromYear }?.id } }
        load(classes) { api.classes().data.sortedBy { it.numericOrder }.also { cs -> cs.forEachIndexed { i, cl -> mapping[cl.id] = cs.getOrNull(i + 1)?.id } } }
    }
    fun run() = act {
        val body = buildJsonObject {
            put("from_academic_year_id", fromYear ?: 0); put("to_academic_year_id", toYear ?: 0)
            put("mappings", buildJsonArray { mapping.forEach { (f, t) -> if (t != null) add(buildJsonObject { put("from_class_id", f); put("to_class_id", t) }) } })
            put("exclude_student_ids", buildJsonArray { })
        }
        val r = api.promotionRun(body); result = r.data
        r.message ?: "Students promoted."
    }
}

@Composable
fun PromotionScreen() {
    val vm = screenViewModel { PromotionViewModel(it) }
    MessageEffect(vm)
    LaunchedEffect(Unit) { vm.start() }
    LoadContent(vm.years.value, onRetry = { vm.start() }) { years ->
        LoadContent(vm.classes.value, onRetry = { vm.start() }) { classes ->
            ScreenList {
                item { Text("Move every student up one class for the new session. Class history is saved permanently and can't be edited later.", color = MaterialTheme.colorScheme.onSurfaceVariant) }
                item {
                    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        Dropdown("From session", years.map { it.id to it.title }, vm.fromYear, { vm.fromYear = it }, Modifier.weight(1f))
                        Dropdown("To session", years.map { it.id to it.title }, vm.toYear, { vm.toYear = it }, Modifier.weight(1f))
                    }
                }
                item { SectionHeader("Class → next class") }
                items(classes) { cl ->
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Text(cl.name, Modifier.weight(0.8f), style = MaterialTheme.typography.titleSmall)
                        Icon(Icons.AutoMirrored.Outlined.ArrowForward, null, Modifier.padding(horizontal = 6.dp))
                        Dropdown("Goes to", listOf<Pair<Int?, String>>(null to "Pass out / leave") + classes.map { it.id to it.name }, vm.mapping[cl.id], { vm.mapping[cl.id] = it }, Modifier.weight(1.2f))
                    }
                }
                item {
                    Button(onClick = { vm.confirm = true }, enabled = !vm.busy && vm.fromYear != null && vm.toYear != null && vm.fromYear != vm.toYear, modifier = Modifier.fillMaxWidth().height(52.dp)) { Text("Promote students") }
                }
                vm.result?.let { item { ElevatedCard { Column(Modifier.padding(14.dp)) { JsonResultView(it) } } } }
            }
        }
    }
    if (vm.confirm) ConfirmDialog("Promote all students?", "This moves students to their new classes for the new session and can't be undone from the app.", confirm = "Promote", onDismiss = { vm.confirm = false }) {
        vm.confirm = false; vm.run()
    }
}
