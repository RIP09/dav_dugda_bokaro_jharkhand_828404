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
import androidx.compose.ui.platform.testTag
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

/** Add / edit form for any server module, built from the module's field list. */
class RecordFormViewModel(c: AppContainer, val key: String, val id: String?) : BaseViewModel(c) {
    var meta by mutableStateOf<ModuleMeta?>(null)
    var original by mutableStateOf<JsonObject?>(null)
    val state = loadState<Unit>()
    val values = mutableStateMapOf<String, String>()
    val labels = mutableStateMapOf<String, String>()   // file names shown for uploads
    val errors = mutableStateMapOf<String, String>()
    val refOptions = mutableStateMapOf<String, List<RefOption>>()
    var saved by mutableStateOf<String?>(null)
    var uploading by mutableStateOf<String?>(null)

    fun start() = load(state) {
        val m = c.meta.module(key); meta = m
        val row = id?.let { api.record(key, it).data }
        original = row
        if (row?.isLocked == true) throw IllegalStateException("This record is locked: " + (row.str("_lock_reason") ?: "it can't be edited."))
        editableFields(m).forEach { f ->
            values[f.name] = formText(f, row)
            if (f.type == "file" || f.type == "image") labels[f.name] = row?.str(f.name)?.substringAfterLast('/') ?: ""
        }
        editableFields(m).filter { it.type == "ref" && it.ref != null }.forEach { f ->
            runCatching { refOptions[f.name] = c.meta.refChoices(f.ref!!) }
        }
    }

    /** Choices for a ref field, narrowed to the chosen parent (e.g. only Class 6's sections once Class 6 is picked). */
    fun choicesFor(f: FieldMeta): List<RefOption>? {
        val m = meta ?: return null
        val all = refOptions[f.name] ?: return null
        val dep = f.dependency(editableFields(m))
        return all.forParent(dep, dep?.let { values[it.field] })
    }

    /** Set a value; pickers that depend on it are cleared if their choice no longer fits, or filled when only one fits. */
    fun change(name: String, value: String) {
        values[name] = value; errors.remove(name)
        val m = meta ?: return
        val fields = editableFields(m)
        fields.filter { it.type == "ref" && it.dependency(fields)?.field == name }.forEach { child ->
            val opts = choicesFor(child) ?: return@forEach
            val cur = values[child.name].orEmpty()
            if (cur.isNotBlank() && opts.none { it.id == cur }) change(child.name, "")
            if (values[child.name].isNullOrBlank() && opts.size == 1 && child.required) change(child.name, opts[0].id)
        }
    }

    fun upload(field: String, uri: Uri) {
        viewModelScope.launch {
            uploading = field
            try {
                val r = api.upload(ServerFiles.part(c.appContext, uri, "file"), ServerFiles.text(key)).data!!
                values[field] = r.path; labels[field] = r.path.substringAfterLast('/')
            } catch (e: CancellationException) { throw e } catch (e: Throwable) { message = c.errorMessage(e) }
            finally { uploading = null }
        }
    }

    fun save() {
        val m = meta ?: return
        errors.clear()
        val fields = editableFields(m).filter { id == null || !it.immutable }
        fields.filter { it.required && it.type != "bool" && values[it.name].isNullOrBlank() }.forEach { errors[it.name] = "Required" }
        if (errors.isNotEmpty()) { message = "Please fill the required fields."; return }
        val body = buildJsonObject {
            fields.forEach { f ->
                val now = values[f.name].orEmpty()
                if (id == null) { if (now.isNotBlank() || f.type == "bool") put(f.name, toJsonValue(f, now)) }
                else if (now != formText(f, original)) put(f.name, toJsonValue(f, now))
            }
        }
        if (id != null && body.isEmpty()) { message = "Nothing changed."; return }
        if (busy) return
        viewModelScope.launch {
            busy = true
            try {
                val r = if (id == null) api.createRecord(key, body) else api.updateRecord(key, id, body)
                c.meta.invalidateRefs(key)
                saved = r.data?.str("id") ?: id ?: ""
                message = r.message ?: if (id == null) "Saved." else "Changes saved."
            } catch (e: CancellationException) { throw e } catch (e: Throwable) {
                val f = c.failure(e)
                f.fields.forEach { (k, v) -> errors[k] = v.firstOrNull() ?: "Invalid" }
                message = f.message
            } finally { busy = false }
        }
    }
}

@Composable
fun RecordFormScreen(key: String, id: String?, onDone: (savedId: String) -> Unit) {
    val vm = screenViewModel(key = "form_${key}_${id ?: "new"}") { RecordFormViewModel(it, key, id) }
    MessageEffect(vm)
    LaunchedEffect(Unit) { vm.start() }
    LaunchedEffect(vm.saved) { vm.saved?.let(onDone) }
    var pickFor by remember { mutableStateOf<String?>(null) }
    val picker = rememberLauncherForActivityResult(ActivityResultContracts.GetContent()) { uri -> val f = pickFor; if (uri != null && f != null) vm.upload(f, uri) }

    LoadContent(vm.state.value, onRetry = { vm.start() }) {
        val meta = vm.meta ?: return@LoadContent
        Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
            Text(if (id == null) "New ${meta.title.removeSuffix("s")}" else "Edit ${vm.original?.let { rowTitle(meta, it) } ?: ""}",
                style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.SemiBold)
            if (id != null) Text("Every change is saved in the history with your name and time.", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            editableFields(meta).forEach { f ->
                val locked = id != null && f.immutable
                val label = f.title + if (f.required) " *" else ""
                val value = vm.values[f.name].orEmpty()
                val set: (String) -> Unit = { vm.change(f.name, it) }
                Column {
                    when {
                        locked -> OutlinedTextField(value = displayValue(f, vm.original ?: JsonObject(emptyMap())), onValueChange = {}, readOnly = true, enabled = false,
                            label = { Text(f.title) }, leadingIcon = { Icon(Icons.Outlined.Lock, null) }, modifier = Modifier.fillMaxWidth(),
                            supportingText = { Text("Permanent — can't be changed after saving") })
                        f.type == "select" -> Dropdown(label, (if (f.required) emptyList() else listOf("" to "—")) + f.options.orEmpty().map { it.key to it.label }, value, set, Modifier.fillMaxWidth())
                        f.type == "ref" -> {
                            val opts = vm.choicesFor(f)
                            val dep = f.dependency(editableFields(meta))
                            val parent = dep?.let { d -> editableFields(meta).firstOrNull { it.name == d.field } }
                            when {
                                opts == null -> FormField(label + " (id)", value, set, number = true)
                                dep != null && vm.values[dep.field].isNullOrBlank() -> OutlinedTextField(value = "", onValueChange = {}, readOnly = true, enabled = false,
                                    label = { Text("$label — choose ${parent?.title ?: "the class"} first") }, modifier = Modifier.fillMaxWidth().testTag("ref_${f.name}_waiting"))
                                else -> Dropdown(label, (if (f.required) emptyList() else listOf("" to "—")) + opts.map { it.id to it.label }, value, set, Modifier.fillMaxWidth())
                            }
                        }
                        f.type == "bool" -> Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                            Text(f.title, Modifier.weight(1f), style = MaterialTheme.typography.bodyLarge)
                            Switch(checked = value == "true", onCheckedChange = { set(if (it) "true" else "false") })
                        }
                        f.type == "date" -> DateField(label, value, set)
                        f.type == "file" || f.type == "image" -> OutlinedCard(Modifier.fillMaxWidth()) {
                            Row(Modifier.padding(12.dp), verticalAlignment = Alignment.CenterVertically) {
                                Icon(if (f.type == "image") Icons.Outlined.Image else Icons.Outlined.AttachFile, null)
                                Spacer(Modifier.width(10.dp))
                                Column(Modifier.weight(1f)) {
                                    Text(label, style = MaterialTheme.typography.labelMedium)
                                    Text(vm.labels[f.name].orEmpty().ifBlank { "No file chosen" }, maxLines = 1, overflow = TextOverflow.Ellipsis)
                                }
                                if (vm.uploading == f.name) CircularProgressIndicator(Modifier.size(22.dp))
                                else TextButton(onClick = { pickFor = f.name; picker.launch(if (f.type == "image") "image/*" else "*/*") }) { Text("Choose") }
                            }
                        }
                        else -> FormField(
                            label + when (f.type) { "time" -> " (HH:MM)"; "datetime" -> " (YYYY-MM-DD HH:MM)"; "money" -> " (₹)"; else -> "" },
                            value, set, number = f.type == "number" || f.type == "money", multiline = f.type == "textarea",
                        )
                    }
                    val err = vm.errors[f.name]
                    if (err != null) Text(err, color = MaterialTheme.colorScheme.error, style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(start = 12.dp, top = 2.dp))
                    else if (!f.help.isNullOrBlank() && !locked) Text(f.help, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant, modifier = Modifier.padding(start = 12.dp, top = 2.dp))
                }
            }
            Button(onClick = { vm.save() }, enabled = !vm.busy && vm.uploading == null, modifier = Modifier.fillMaxWidth().height(52.dp)) {
                if (vm.busy) CircularProgressIndicator(Modifier.size(20.dp), strokeWidth = 2.dp, color = MaterialTheme.colorScheme.onPrimary)
                else Text(if (id == null) "Save" else "Save changes")
            }
            Spacer(Modifier.height(60.dp))
        }
    }
}
