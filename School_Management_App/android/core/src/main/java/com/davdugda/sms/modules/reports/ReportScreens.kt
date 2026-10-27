package com.davdugda.sms.modules.reports

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


// ============================== Attendance report (daily / monthly, PDF / Excel) ==============================

class AttendanceReportViewModel(c: AppContainer) : BaseViewModel(c) {
    var classId by mutableStateOf<Int?>(null)
    var sectionId by mutableStateOf<Int?>(null)
    var month by mutableStateOf(YearMonth.now().toString())
    var report by mutableStateOf<Load<JsonElement?>?>(null)
    val daily = loadState<JsonElement?>()
    fun loadDaily() = load(daily) { api.attendanceDaily(today()).data }
    fun run() {
        val s = sectionId ?: return
        viewModelScope.launch {
            report = Load.Loading
            report = try { Load.Ok(api.attendanceReport(s, month).data) } catch (e: CancellationException) { throw e } catch (e: Throwable) { Load.Err(c.errorMessage(e)) }
        }
    }
}

@Composable
fun AttendanceReportScreen() {
    val vm = screenViewModel { AttendanceReportViewModel(it) }
    val scope = screenViewModel { ClassScope(it) }
    val openFile = rememberFileOpener()
    LaunchedEffect(Unit) { vm.loadDaily() }
    LaunchedEffect(vm.sectionId, vm.month) { vm.run() }
    ScreenList {
        item {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                ClassSectionPicker(scope.classes.value, vm.classId, vm.sectionId) { cl, s -> vm.classId = cl; vm.sectionId = s }
                Row(verticalAlignment = Alignment.CenterVertically) {
                    IconButton(onClick = { vm.month = YearMonth.parse(vm.month).minusMonths(1).toString() }) { Icon(Icons.Outlined.ChevronLeft, "Previous month") }
                    Text(YearMonth.parse(vm.month).let { "${it.month.name.cap().lowercase().replaceFirstChar { c -> c.uppercase() }} ${it.year}" }, Modifier.weight(1f), style = MaterialTheme.typography.titleMedium)
                    IconButton(onClick = { vm.month = YearMonth.parse(vm.month).plusMonths(1).toString() }) { Icon(Icons.Outlined.ChevronRight, "Next month") }
                }
                vm.sectionId?.let { s ->
                    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        OutlinedButton(onClick = { openFile("pdf/attendance/$s?month=${vm.month}", "attendance_${s}_${vm.month}.pdf") }) { Icon(Icons.Outlined.PictureAsPdf, null); Spacer(Modifier.width(6.dp)); Text("PDF") }
                        OutlinedButton(onClick = { openFile("export/student_attendance?format=xlsx&filter[section_id]=$s&month=${vm.month}", "attendance_${s}_${vm.month}.xlsx") }) { Icon(Icons.Outlined.TableChart, null); Spacer(Modifier.width(6.dp)); Text("Excel") }
                    }
                }
            }
        }
        when (val r = vm.report) {
            null -> {
                item { SectionHeader("Today, class by class") }
                item { LoadContent(vm.daily.value, onRetry = { vm.loadDaily() }) { JsonResultView(it) } }
                item { Text("Pick a class and section to see the month register.", color = MaterialTheme.colorScheme.onSurfaceVariant) }
            }
            is Load.Loading -> item { Box(Modifier.fillMaxWidth().padding(32.dp), contentAlignment = Alignment.Center) { CircularProgressIndicator() } }
            is Load.Err -> item { Text(r.message, color = MaterialTheme.colorScheme.error) }
            is Load.Ok -> {
                val data = r.data
                val rows = (data as? JsonObject)?.let { (it["students"] ?: it["rows"] ?: it["data"]) as? JsonArray } ?: (data as? JsonArray)
                if (rows == null) item { JsonResultView(data) }
                else {
                    (data as? JsonObject)?.let { o -> o["totals"]?.let { t -> item { ElevatedCard { Column(Modifier.padding(12.dp)) { JsonResultView(t) } } } } }
                    items(rows.filterIsInstance<JsonObject>()) { s -> AttendanceStudentRow(s) }
                }
            }
        }
    }
}

@Composable
private fun AttendanceStudentRow(s: JsonObject) {
    val name = s.str("name") ?: s.str("student_name") ?: (s["student"] as? JsonObject)?.str("name") ?: "Student"
    val roll = s.str("roll_no")
    val totals = (s["totals"] as? JsonObject) ?: s
    val present = totals.str("present") ?: totals.str("P")
    val absent = totals.str("absent") ?: totals.str("A")
    val pct = totals.str("percentage") ?: totals.str("percent")
    val days = (s["days"] as? JsonObject)?.entries?.sortedBy { it.key.takeLast(2).toIntOrNull() ?: 0 }?.joinToString("") { (it.value.asText().take(1).uppercase().ifEmpty { "·" }) }
        ?: (s["days"] as? JsonArray)?.joinToString("") { (it as? JsonObject)?.str("status")?.take(1)?.uppercase() ?: it.asText().take(1).uppercase().ifEmpty { "·" } }
    InfoCard(
        title = listOfNotNull(roll?.let { "$it." }, name).joinToString(" "),
        subtitle = listOfNotNull(present?.let { "Present $it" }, absent?.let { "Absent $it" }).joinToString(" · ").ifBlank { null },
        supporting = days,
        trailing = { pct?.let { Text(it.removeSuffix("%") + "%", fontWeight = FontWeight.Bold, color = if ((it.removeSuffix("%").toDoubleOrNull() ?: 100.0) < 75) Color(0xFFC62828) else Color(0xFF2E7D32)) } },
    )
}

// ============================== Audit log (admin) ==============================

class AuditViewModel(c: AppContainer) : BaseViewModel(c) {
    val rows = mutableStateListOf<AuditRow>()
    var state by mutableStateOf<Load<Unit>>(Load.Loading)
    var module by mutableStateOf("")
    var action by mutableStateOf("")
    var page = 1; var lastPage by mutableStateOf(1)
    private var useRecords = false
    private fun q(p: Int) = buildMap {
        put("page", "$p"); put("per_page", "25")
        if (useRecords) { put("sort", "-id"); if (module.isNotEmpty()) put("filter[module]", module); if (action.isNotEmpty()) put("filter[action]", action) }
        else { if (module.isNotEmpty()) put("module", module); if (action.isNotEmpty()) put("action", action) }
    }
    /** GET audit, or the "audit" records module when the server serves the log that way. */
    private suspend fun fetch(p: Int): AuditPage =
        if (useRecords) api.auditRecords(q(p))
        else try { api.audit(q(p)) } catch (e: retrofit2.HttpException) { if (e.code() == 404) { useRecords = true; api.auditRecords(q(p)) } else throw e }
    fun refresh() { viewModelScope.launch {
        state = Load.Loading
        state = try { val r = fetch(1); rows.clear(); rows.addAll(r.data); page = 1; lastPage = r.meta?.lastPage ?: 1; Load.Ok(Unit) }
        catch (e: CancellationException) { throw e } catch (e: Throwable) { Load.Err(c.errorMessage(e)) }
    } }
    fun more() { if (page >= lastPage) return; viewModelScope.launch { runCatching { val r = fetch(page + 1); rows.addAll(r.data); page += 1 } } }
}

@Composable
fun AuditScreen() {
    val vm = screenViewModel { AuditViewModel(it) }
    val c = LocalContainer.current
    val navigate = LocalNavigate.current
    LaunchedEffect(vm.module, vm.action) { vm.refresh() }
    Column(Modifier.fillMaxSize()) {
        Row(Modifier.padding(horizontal = 16.dp), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            Dropdown("Module", listOf("" to "All modules") + c.meta.modules.values.sortedBy { it.title }.map { it.key to it.title }, vm.module, { vm.module = it }, Modifier.weight(1f))
            Dropdown("Action", listOf("" to "All", "create" to "Added", "update" to "Edited", "delete" to "Deleted", "cancel" to "Cancelled", "lock_results" to "Locked"), vm.action, { vm.action = it }, Modifier.weight(0.8f))
        }
        when (val s = vm.state) {
            is Load.Loading -> Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) { CircularProgressIndicator() }
            is Load.Err -> ErrorBox(s.message) { vm.refresh() }
            is Load.Ok -> ScreenList {
                item { Text("Every add, edit, delete and cancel in the school, with who did it. This log can't be edited.", color = MaterialTheme.colorScheme.onSurfaceVariant, style = MaterialTheme.typography.bodySmall) }
                if (vm.rows.isEmpty()) item { EmptyState("No changes recorded.", Icons.Outlined.History) }
                items(vm.rows) { r ->
                    val id = r.recordId?.asText().orEmpty()
                    AuditCard(r, onClick = if (r.module != null && id.isNotEmpty() && !r.action.startsWith("delete")) ({ navigate(Routes.record(r.module, id)) }) else null)
                }
                if (vm.page < vm.lastPage) item { LaunchedEffect(vm.rows.size) { vm.more() }; Box(Modifier.fillMaxWidth().padding(12.dp), contentAlignment = Alignment.Center) { CircularProgressIndicator(Modifier.size(26.dp)) } }
            }
        }
    }
}

// ============================== Import from Excel / CSV (move offline registers in) ==============================

val ImportModules = listOf("students" to "Students", "staff" to "Staff", "parents" to "Parents", "books" to "Library books",
    "inventory_items" to "Inventory items", "fee_structures" to "Fee structures", "transactions" to "Income / expense", "visitors" to "Visitors")

class ImportViewModel(c: AppContainer, preset: String?) : BaseViewModel(c) {
    var module by mutableStateOf(preset?.takeIf { p -> ImportModules.any { it.first == p } } ?: "students")
    var file by mutableStateOf<Uri?>(null)
    var result by mutableStateOf<ImportResult?>(null)
    var wasDryRun by mutableStateOf(true)
    fun run(dry: Boolean) {
        val f = file ?: run { message = "Choose the Excel/CSV file first."; return }
        act(onDone = {}) {
            val r = api.importFile(module, ServerFiles.part(c.appContext, f, "file"), ServerFiles.text(if (dry) "1" else "0"))
            result = r.data; wasDryRun = dry
            r.message ?: if (dry) "Checked. Nothing saved yet." else "Imported."
        }
    }
}

@Composable
fun ImportScreen(preset: String?) {
    val vm = screenViewModel(key = "import_$preset") { ImportViewModel(it, preset) }
    val openFile = rememberFileOpener()
    val ctx = LocalContext.current
    MessageEffect(vm)
    val picker = rememberLauncherForActivityResult(ActivityResultContracts.GetContent()) { if (it != null) { vm.file = it; vm.result = null } }
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Text("Move your paper / Excel registers into the system in 3 steps.", style = MaterialTheme.typography.bodyLarge)
        Dropdown("What to import", ImportModules, vm.module, { vm.module = it; vm.result = null }, Modifier.fillMaxWidth())
        StepCard(1, "Download the template", "Open it in Excel or Google Sheets and fill one row per ${ImportModules.first { it.first == vm.module }.second.lowercase().removeSuffix("s")}.") {
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                OutlinedButton(onClick = { openFile("import/${vm.module}/template?format=xlsx", "${vm.module}_template.xlsx") }) { Text("Excel template") }
                TextButton(onClick = { openFile("import/${vm.module}/template?format=csv", "${vm.module}_template.csv") }) { Text("CSV") }
            }
        }
        StepCard(2, "Choose your filled file", vm.file?.let { "Chosen: " + (it.lastPathSegment ?: "file") } ?: "Excel (.xlsx) or CSV.") {
            OutlinedButton(onClick = { picker.launch("*/*") }) { Icon(Icons.Outlined.UploadFile, null); Spacer(Modifier.width(6.dp)); Text("Choose file") }
        }
        StepCard(3, "Check, then import", "“Check” shows problems without saving anything. When it looks right, tap Import.") {
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                FilledTonalButton(onClick = { vm.run(true) }, enabled = !vm.busy && vm.file != null) { Text("Check") }
                Button(onClick = { vm.run(false) }, enabled = !vm.busy && vm.file != null) { Text("Import") }
            }
        }
        if (vm.busy) LinearProgressIndicator(Modifier.fillMaxWidth())
        vm.result?.let { r ->
            ElevatedCard(Modifier.fillMaxWidth()) {
                Column(Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                    Text(if (vm.wasDryRun) "Check result (nothing saved yet)" else "Import finished", style = MaterialTheme.typography.titleMedium)
                    Text("${if (vm.wasDryRun) "Would create" else "Created"}: ${r.created} · ${if (vm.wasDryRun) "would update" else "updated"}: ${r.updated} · problems: ${r.errors.size}")
                    r.errors.take(100).forEach { e -> Text("Row ${e.row}: ${e.message}", color = MaterialTheme.colorScheme.error, style = MaterialTheme.typography.bodySmall) }
                }
            }
        }
    }
}

@Composable
fun StepCard(n: Int, title: String, text: String, content: @Composable () -> Unit) {
    OutlinedCard(Modifier.fillMaxWidth()) {
        Row(Modifier.padding(14.dp)) {
            Avatar(n.toString(), MaterialTheme.colorScheme.primary, 32)
            Spacer(Modifier.width(12.dp))
            Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
                Text(title, style = MaterialTheme.typography.titleMedium)
                Text(text, style = MaterialTheme.typography.bodyMedium, color = MaterialTheme.colorScheme.onSurfaceVariant)
                content()
            }
        }
    }
}

// ============================== Backup ==============================

@Composable
fun BackupScreen() {
    val openFile = rememberFileOpener()
    Column(Modifier.fillMaxSize().padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        IconBadge(Icons.Outlined.Backup, Color(0xFF455A64), 64)
        Text("Download a full backup", style = MaterialTheme.typography.titleLarge)
        Text("One zip file with every table of the school (Excel-readable CSV + JSON). Keep a copy on your computer or Google Drive every week. " +
            "It's saved in Downloads/DAV School on this phone.", color = MaterialTheme.colorScheme.onSurfaceVariant)
        Button(onClick = { openFile("backup/download", "dav_school_backup_${today()}.zip") }, modifier = Modifier.fillMaxWidth().height(52.dp)) {
            Icon(Icons.Outlined.CloudDownload, null); Spacer(Modifier.width(8.dp)); Text("Download backup now")
        }
        Text("Restoring a backup is done on the server by the person who set it up (see the README), so nobody can overwrite the school's data by mistake from a phone.",
            style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.outline)
    }
}
