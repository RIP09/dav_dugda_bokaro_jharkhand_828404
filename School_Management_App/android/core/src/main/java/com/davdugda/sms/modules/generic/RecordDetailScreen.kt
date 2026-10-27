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

import android.net.Uri
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.FlowRow
import androidx.compose.foundation.layout.ExperimentalLayoutApi
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.ui.draw.clip
import androidx.compose.ui.layout.ContentScale
import coil.compose.AsyncImage

class RecordViewModel(c: AppContainer, val key: String, val id: String) : BaseViewModel(c) {
    var meta by mutableStateOf<ModuleMeta?>(null)
    val state = loadState<JsonObject>()
    var deleted by mutableStateOf(false)

    fun refresh() = load(state, silent = true) {
        meta = c.meta.module(key)
        api.record(key, id).data ?: throw IllegalStateException("Record not found.")
    }

    fun delete() = act("Deleted.", onDone = { c.meta.invalidateRefs(key); deleted = true }) { api.deleteRecord(key, id).message }

    fun runAction(a: ModuleAction, reason: String?, inputs: Map<String, String>) = act("Done.", onDone = { c.meta.invalidateRefs(key); refresh() }) {
        val body = buildJsonObject {
            if (!reason.isNullOrBlank()) put("reason", reason)
            a.inputs.forEach { f -> inputs[f.name]?.takeIf { it.isNotBlank() }?.let { put(f.name, toJsonValue(f, it)) } }
        }
        api.recordAction(key, id, a.key, body).message
    }

    fun uploadPhoto(uri: Uri) = act("Photo updated.", onDone = { refresh() }) {
        val part = ServerFiles.part(c.appContext, uri, "photo")
        if (key == "students") api.studentPhoto(id.toInt(), part).message
        else {
            // other modules: upload then save the path in the first image field
            val up = api.upload(ServerFiles.part(c.appContext, uri, "file"), ServerFiles.text(key)).data!!
            val f = meta?.fields?.firstOrNull { it.type == "image" } ?: return@act "No photo field here."
            api.updateRecord(key, id, buildJsonObject { put(f.name, up.path) }).message
        }
    }
}

@OptIn(ExperimentalLayoutApi::class)
@Composable
fun RecordDetailScreen(key: String, id: String, onBack: () -> Unit) {
    val vm = screenViewModel(key = "rec_${key}_$id") { RecordViewModel(it, key, id) }
    val navigate = LocalNavigate.current
    val user = LocalUser.current
    val openFile = rememberFileOpener()
    MessageEffect(vm)
    LaunchedEffect(Unit) { vm.refresh() }
    LaunchedEffect(vm.deleted) { if (vm.deleted) onBack() }
    var confirmDelete by remember { mutableStateOf(false) }
    var pendingAction by remember { mutableStateOf<ModuleAction?>(null) }
    val photoPicker = rememberLauncherForActivityResult(ActivityResultContracts.GetContent()) { uri -> if (uri != null) vm.uploadPhoto(uri) }
    val tint = tintFor(key)

    LoadContent(vm.state.value, onRetry = { vm.refresh() }) { row ->
        val meta = vm.meta
        val locked = row.isLocked
        val canEdit = !locked && (row["_can_edit"]?.let { row.bool("_can_edit") } ?: (meta?.permissions?.update == true))
        val canDelete = !locked && (row["_can_delete"]?.let { row.bool("_can_delete") } ?: (meta?.permissions?.delete == true))
        val imageField = meta?.fields?.firstOrNull { it.type == "image" }
        val photoUrl = imageField?.let { row.str(it.name + "_url") } ?: row.str("photo_url")
        Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
            // Header
            Row(verticalAlignment = Alignment.CenterVertically) {
                if (photoUrl != null) AsyncImage(photoUrl, null, Modifier.size(64.dp).clip(RoundedCornerShape(16.dp)), contentScale = ContentScale.Crop)
                else IconBadge(iconFor(meta?.icon, key), tint, 56)
                Spacer(Modifier.width(14.dp))
                Column(Modifier.weight(1f)) {
                    Text(rowTitle(meta, row), style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.SemiBold)
                    val sub = rowSubtitle(meta, row)
                    if (sub.isNotBlank()) Text(sub, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    Text(meta?.title ?: key, style = MaterialTheme.typography.labelMedium, color = tint)
                }
                row.str("status")?.let { StatusChip(it) }
            }
            if (locked) LockBanner(row.str("_lock_reason"), meta?.lockNote)

            // Buttons
            FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                if (canEdit) Button(onClick = { navigate(Routes.edit(key, id)) }) { Icon(Icons.Outlined.Edit, null, Modifier.size(18.dp)); Spacer(Modifier.width(6.dp)); Text("Edit") }
                if (canEdit && (key == "students" || imageField != null)) OutlinedButton(onClick = { photoPicker.launch("image/*") }) {
                    Icon(Icons.Outlined.PhotoCamera, null, Modifier.size(18.dp)); Spacer(Modifier.width(6.dp)); Text(if (photoUrl == null) "Add photo" else "Change photo")
                }
                val allowed = (row["_actions"] as? JsonArray)?.map { it.asText() }
                val actions = meta?.actions.orEmpty().filter { a -> if (allowed != null) a.key in allowed else actionVisible(a, row) }
                actions.forEach { a ->
                    if (a.kind == "pdf") OutlinedButton(onClick = { openFile(pdfPath(key, a, id, row), "${key}_${id}.pdf") }) {
                        Icon(Icons.Outlined.PictureAsPdf, null, Modifier.size(18.dp)); Spacer(Modifier.width(6.dp)); Text(a.label)
                    } else FilledTonalButton(onClick = { pendingAction = a }, enabled = !vm.busy) {
                        Icon(if (a.key.contains("cancel")) Icons.Outlined.Block else Icons.Outlined.Bolt, null, Modifier.size(18.dp)); Spacer(Modifier.width(6.dp)); Text(a.label)
                    }
                }
                // Well-known printouts when the server did not list a print action
                if (actions.none { it.kind == "pdf" }) extraPdfs(key).forEach { (label, path) ->
                    OutlinedButton(onClick = { openFile(path(id), "${label.replace(' ', '_')}_$id.pdf") }) { Icon(Icons.Outlined.PictureAsPdf, null, Modifier.size(18.dp)); Spacer(Modifier.width(6.dp)); Text(label) }
                }
                if (meta?.permissions?.history == true || user.isAdmin) OutlinedButton(onClick = { navigate(Routes.history(key, id)) }) { Icon(Icons.Outlined.History, null, Modifier.size(18.dp)); Spacer(Modifier.width(6.dp)); Text("History") }
                if (canDelete) TextButton(onClick = { confirmDelete = true }, colors = ButtonDefaults.textButtonColors(contentColor = MaterialTheme.colorScheme.error)) {
                    Icon(Icons.Outlined.Delete, null, Modifier.size(18.dp)); Spacer(Modifier.width(6.dp)); Text("Delete")
                }
            }

            // Fields
            ElevatedCard(colors = CardDefaults.elevatedCardColors(containerColor = MaterialTheme.colorScheme.surface)) {
                Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    val fields = meta?.fields.orEmpty().ifEmpty { row.keys.filter { !it.startsWith("_") }.map { FieldMeta(it) } }
                    fields.forEach { f ->
                        val v = displayValue(f, row)
                        if (f.type == "file" && row.str(f.name + "_url") != null) {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Column(Modifier.weight(1f)) { Text(f.title, style = MaterialTheme.typography.labelMedium, color = MaterialTheme.colorScheme.onSurfaceVariant); Text(v) }
                                TextButton(onClick = { openFile(row.str(f.name + "_url")!!, v.ifBlank { "file" }) }) { Text("Open") }
                            }
                        } else if (f.type != "image") {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Column(Modifier.weight(1f)) {
                                    Text(f.title, style = MaterialTheme.typography.labelMedium, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                    Text(v.ifBlank { "—" }, style = MaterialTheme.typography.bodyLarge)
                                }
                                if (f.immutable) Icon(Icons.Outlined.Lock, "Can't be changed", Modifier.size(14.dp), tint = MaterialTheme.colorScheme.outline)
                            }
                        }
                    }
                    HorizontalDivider()
                    Text("Added ${prettyDate(row.str("created_at"))}" + (row.str("updated_at")?.let { " · last changed ${prettyDate(it)}" } ?: ""),
                        style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.outline)
                }
            }
            Spacer(Modifier.height(40.dp))
        }
    }

    if (confirmDelete) ConfirmDialog("Delete this record?", "This removes it for everyone. The change is recorded in the history.", onDismiss = { confirmDelete = false }) {
        confirmDelete = false; vm.delete()
    }
    pendingAction?.let { a ->
        val needsReason = a.reason || a.needsReason || a.key.contains("cancel")
        var reason by remember(a.key) { mutableStateOf("") }
        val inputs = remember(a.key) { mutableStateMapOf<String, String>().apply { a.inputs.forEach { f -> put(f.name, formText(f, null).ifBlank { if (f.type == "date") today() else "" }) } } }
        val missing = a.inputs.any { it.required && inputs[it.name].isNullOrBlank() } || (needsReason && reason.isBlank())
        AlertDialog(
            onDismissRequest = { pendingAction = null },
            title = { Text(a.label) },
            text = {
                Column(Modifier.verticalScroll(rememberScrollState()), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    Text(a.confirm ?: if (needsReason) "Please write why. This is saved permanently in the record's history." else "Are you sure?")
                    a.inputs.forEach { f ->
                        val label = f.title + if (f.required) " *" else ""
                        val v = inputs[f.name].orEmpty()
                        when (f.type) {
                            "select" -> Dropdown(label, f.options.orEmpty().map { it.key to it.label }, v, { inputs[f.name] = it }, Modifier.fillMaxWidth())
                            "date" -> DateField(label, v, { inputs[f.name] = it })
                            else -> FormField(label, v, { inputs[f.name] = it }, number = f.type == "money" || f.type == "number", multiline = f.type == "textarea")
                        }
                    }
                    if (needsReason) FormField("Reason *", reason, { reason = it }, multiline = true)
                }
            },
            confirmButton = { TextButton(enabled = !missing, onClick = { pendingAction = null; vm.runAction(a, reason, inputs.toMap()) }) { Text("Confirm") } },
            dismissButton = { TextButton(onClick = { pendingAction = null }) { Text("Back") } },
        )
    }
}

/** Printouts every school needs, offered on the matching records (pdf/{doc}/{id}). */
private fun extraPdfs(key: String): List<Pair<String, (String) -> String>> = when (key) {
    "students" -> listOf("ID card" to { id -> "pdf/id-card/$id" }, "Report card" to { id -> "pdf/report-card/$id" }, "Fee statement" to { id -> "pdf/fee-statement/$id" })
    "staff", "teachers" -> listOf("ID card" to { id -> "pdf/staff-id-card/$id" })
    "payments" -> listOf("Receipt" to { id -> "pdf/receipt/$id" })
    "invoices" -> listOf("Invoice" to { id -> "pdf/invoice/$id" })
    "certificates" -> listOf("Certificate" to { id -> "pdf/certificate/$id" })
    "payslips" -> listOf("Salary slip" to { id -> "pdf/salary-slip/$id" })
    else -> emptyList()
}
