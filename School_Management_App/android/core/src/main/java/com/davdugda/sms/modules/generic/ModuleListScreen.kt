package com.davdugda.sms.modules.generic

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

import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.rememberScrollState

/** List of any server module: search, filters, paging, lock badges, add button, export/import. */
class ModuleListViewModel(c: AppContainer, val key: String) : BaseViewModel(c) {
    var meta by mutableStateOf<ModuleMeta?>(null)
    val rows = mutableStateListOf<JsonObject>()
    var state by mutableStateOf<Load<Unit>>(Load.Loading)
    var query by mutableStateOf("")
    val filters = mutableStateMapOf<String, String>()
    val refOptions = mutableStateMapOf<String, List<Pair<String, String>>>()
    var page = 1
    var lastPage by mutableStateOf(1)
    var total by mutableStateOf(0)
    var loadingMore by mutableStateOf(false)

    fun params(p: Int): Map<String, String> = buildMap {
        put("page", p.toString()); put("per_page", "25"); put("sort", "-id")
        if (query.isNotBlank()) put("q", query.trim())
        filters.forEach { (k, v) -> if (v.isNotEmpty()) put("filter[$k]", v) }
    }

    fun refresh() {
        viewModelScope.launch {
            if (rows.isEmpty()) state = Load.Loading
            try {
                val m = c.meta.module(key); meta = m
                val r = api.records(key, params(1))
                rows.clear(); rows.addAll(r.data)
                page = 1; lastPage = r.meta?.lastPage ?: 1; total = r.meta?.total ?: r.data.size
                state = Load.Ok(Unit)
                m.fields.filter { it.filter && it.type == "ref" && it.ref != null && refOptions[it.name] == null }.forEach { f ->
                    runCatching { refOptions[f.name] = c.meta.refOptions(f.ref!!) }
                }
            } catch (e: CancellationException) { throw e } catch (e: Throwable) { state = Load.Err(c.errorMessage(e)) }
        }
    }

    fun more() {
        if (loadingMore || page >= lastPage) return
        viewModelScope.launch {
            loadingMore = true
            try { val r = api.records(key, params(page + 1)); rows.addAll(r.data); page += 1; lastPage = r.meta?.lastPage ?: page }
            catch (e: CancellationException) { throw e } catch (e: Throwable) { message = c.errorMessage(e) }
            finally { loadingMore = false }
        }
    }

    fun exportPath(format: String) = "export/$key?format=$format" + filters.filter { it.value.isNotEmpty() }.entries.joinToString("") { "&filter[${it.key}]=${it.value}" } +
        (if (query.isNotBlank()) "&q=" + android.net.Uri.encode(query) else "")
}

@Composable
fun ModuleListScreen(key: String) {
    val vm = screenViewModel(key = "list_$key") { ModuleListViewModel(it, key) }
    val navigate = LocalNavigate.current
    val openFile = rememberFileOpener()
    val user = LocalUser.current
    MessageEffect(vm)
    LaunchedEffect(Unit) { vm.refresh() }
    var first by remember { mutableStateOf(true) }
    LaunchedEffect(vm.query) { if (first) { first = false; return@LaunchedEffect }; delay(400); vm.refresh() }
    val area = areaOfModule(key)
    val tint = area?.tint ?: MaterialTheme.colorScheme.primary
    val meta = vm.meta
    Scaffold(
        containerColor = MaterialTheme.colorScheme.background,
        floatingActionButton = {
            if (meta?.permissions?.create == true) ExtendedFloatingActionButton(
                onClick = { navigate(Routes.create(key)) }, icon = { Icon(Icons.Outlined.Add, null) }, text = { Text("Add") })
        },
    ) { pad ->
        Column(Modifier.padding(pad).fillMaxSize()) {
            Column(Modifier.padding(horizontal = 16.dp)) {
                SearchField(vm.query, { vm.query = it }, "Search ${meta?.title ?: ""}".trim())
                val filterFields = meta?.fields?.filter { it.filter && it.type in setOf("select", "ref", "bool") }.orEmpty()
                val classic = area?.classic?.get(key)?.takeIf { it.second.route.isNotEmpty() && !(user.isParent || user.isStudent) }
                if (filterFields.isNotEmpty() || meta?.permissions?.export == true || meta?.permissions?.`import` == true || classic != null) {
                    Row(Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()).padding(vertical = 6.dp), horizontalArrangement = Arrangement.spacedBy(8.dp), verticalAlignment = Alignment.CenterVertically) {
                        filterFields.forEach { f ->
                            val opts: List<Pair<String, String>> = listOf("" to "All") + when (f.type) {
                                "select" -> f.options.orEmpty().map { it.key to it.label }
                                "bool" -> listOf("1" to "Yes", "0" to "No")
                                else -> vm.refOptions[f.name].orEmpty()
                            }
                            Dropdown(f.title, opts, vm.filters[f.name] ?: "", { v -> vm.filters[f.name] = v; vm.refresh() }, Modifier.width(170.dp))
                        }
                        if (classic != null) AssistChip(onClick = { navigate(classic.second.route) }, label = { Text(classic.first) }, leadingIcon = { Icon(classic.second.icon, null, Modifier.size(18.dp)) })
                        if (meta?.permissions?.export == true) {
                            var open by remember { mutableStateOf(false) }
                            Box {
                                AssistChip(onClick = { open = true }, label = { Text("Export") }, leadingIcon = { Icon(Icons.Outlined.Download, null, Modifier.size(18.dp)) })
                                DropdownMenu(open, { open = false }) {
                                    listOf("xlsx" to "Excel (.xlsx)", "pdf" to "PDF", "csv" to "CSV").forEach { (fmt, label) ->
                                        DropdownMenuItem(text = { Text(label) }, onClick = { open = false; openFile(vm.exportPath(fmt), "${key}_export.$fmt") })
                                    }
                                }
                            }
                        }
                        if (meta?.permissions?.`import` == true) AssistChip(onClick = { navigate(Routes.screen("import", key)) }, label = { Text("Import") }, leadingIcon = { Icon(Icons.Outlined.UploadFile, null, Modifier.size(18.dp)) })
                    }
                }
            }
            when (val s = vm.state) {
                is Load.Loading -> Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) { CircularProgressIndicator() }
                is Load.Err -> ErrorBox(s.message) { vm.refresh() }
                is Load.Ok -> ScreenList {
                    item { Text("${vm.total} record(s)" + (if (meta?.lockable == true) " · 🔒 = permanent, can't be edited" else ""), style = MaterialTheme.typography.labelMedium, color = MaterialTheme.colorScheme.onSurfaceVariant) }
                    if (vm.rows.isEmpty()) item { EmptyState(if (vm.query.isNotBlank() || vm.filters.values.any { it.isNotEmpty() }) "Nothing matches. Try other words or filters." else "Nothing here yet." + if (meta?.permissions?.create == true) " Tap Add to create the first one." else "") }
                    items(vm.rows, key = { it.str("id") ?: it.hashCode().toString() }) { row ->
                        RecordRow(meta, key, row, tint) { row.str("id")?.let { navigate(Routes.record(key, it)) } }
                    }
                    if (vm.page < vm.lastPage) item {
                        LaunchedEffect(vm.rows.size) { vm.more() }
                        Box(Modifier.fillMaxWidth().padding(12.dp), contentAlignment = Alignment.Center) { CircularProgressIndicator(Modifier.size(28.dp)) }
                    }
                }
            }
        }
    }
}

@Composable
fun RecordRow(meta: ModuleMeta?, key: String, row: JsonObject, tint: Color, onClick: () -> Unit) {
    val status = row.str("status")
    val title = rowTitle(meta, row)
    InfoCard(
        title = title,
        subtitle = rowSubtitle(meta, row).ifBlank { null },
        leading = { IconBadge(iconFor(meta?.icon, key), tint) },
        trailing = {
            Column(horizontalAlignment = Alignment.End, verticalArrangement = Arrangement.spacedBy(4.dp)) {
                if (row.isLocked) LockBadge(small = true)
                if (status != null) StatusChip(status)
            }
        },
        onClick = onClick,
    )
}
