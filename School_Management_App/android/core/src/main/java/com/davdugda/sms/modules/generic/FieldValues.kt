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

import retrofit2.HttpException

// ---------- Values: how a field of a record is shown and sent ----------

/** Human-readable value of [f] in [row]. */
fun displayValue(f: FieldMeta, row: JsonObject): String {
    val raw = row[f.name]
    row.str(f.name + "_label")?.let { return it }
    if (raw == null || raw is JsonNull) return ""
    val text = raw.asText()
    return when (f.type) {
        "select" -> f.options?.firstOrNull { it.key == text }?.label ?: text.cap()
        "bool" -> if (row.bool(f.name)) "Yes" else "No"
        "money" -> text.toDoubleOrNull()?.let { rupees(it) } ?: text
        "date" -> prettyDate(text)
        "datetime" -> prettyDate(text) + (text.drop(11).take(5).takeIf { it.length == 5 }?.let { " $it" } ?: "")
        "file", "image" -> text.substringAfterLast('/')
        "json" -> if (raw is JsonObject || raw is JsonArray) raw.toString() else text
        else -> text
    }
}

fun rowTitle(meta: ModuleMeta?, row: JsonObject): String =
    row.str("_title") ?: meta?.labelField?.let { row.str(it) } ?: row.str("name") ?: row.str("title") ?: ("#" + (row.str("id") ?: ""))

fun rowSubtitle(meta: ModuleMeta?, row: JsonObject): String =
    row.str("_subtitle") ?: meta?.subtitleFields?.mapNotNull { k ->
        val f = meta.fields.firstOrNull { it.name == k }
        (if (f != null) displayValue(f, row) else row.str(k + "_label") ?: row.str(k))?.takeIf { it.isNotBlank() }
    }?.joinToString(" · ").orEmpty()

/** Form text for a field (what the input box holds). */
fun formText(f: FieldMeta, row: JsonObject?): String {
    val raw = row?.get(f.name) ?: f.default
    if (raw == null || raw is JsonNull) return ""
    return when (f.type) {
        "bool" -> if ((raw as? JsonPrimitive)?.let { it.booleanOrNull ?: (it.intOrNull == 1) } == true) "true" else "false"
        "date" -> raw.asText().take(10)
        "json" -> if (raw is JsonPrimitive) raw.content else raw.toString()
        else -> raw.asText()
    }
}

/** Converts the form text back to JSON for the API. */
fun toJsonValue(f: FieldMeta, text: String): JsonElement {
    val t = text.trim()
    if (t.isEmpty()) return if (f.type == "bool") JsonPrimitive(false) else JsonNull
    return when (f.type) {
        "number" -> t.toLongOrNull()?.let { JsonPrimitive(it) } ?: t.toDoubleOrNull()?.let { JsonPrimitive(it) } ?: JsonPrimitive(t)
        "money" -> t.replace(",", "").toDoubleOrNull()?.let { JsonPrimitive(it) } ?: JsonPrimitive(t)
        "bool" -> JsonPrimitive(t == "true")
        "ref" -> t.toLongOrNull()?.let { JsonPrimitive(it) } ?: JsonPrimitive(t)
        "select" -> JsonPrimitive(t)
        else -> JsonPrimitive(t)
    }
}

/** Fields a form shows: everything except server-computed ones. */
fun editableFields(meta: ModuleMeta) = meta.fields.filter { !it.readonly && it.type != "json" && !it.name.startsWith("_") && it.name !in setOf("id", "created_at", "updated_at") }

/** Evaluates an action's "when" rule like "status!=admitted" or "status==draft" (true when there is no rule). */
fun actionVisible(a: ModuleAction, row: JsonObject): Boolean {
    val rule = a.`when`?.trim().orEmpty()
    if (rule.isEmpty()) return true
    return rule.split("&&").all { part ->
        val p = part.trim()
        when {
            "!=" in p -> { val (k, v) = p.split("!=", limit = 2); (row.str(k.trim()) ?: "") !in v.trim().split('|') }
            "==" in p -> { val (k, v) = p.split("==", limit = 2); (row.str(k.trim()) ?: "") in v.trim().split('|') }
            "=" in p -> { val (k, v) = p.split("=", limit = 2); (row.str(k.trim()) ?: "") in v.trim().split('|') }
            p.startsWith("!") -> !row.bool(p.drop(1))
            else -> row.bool(p)
        }
    }
}

/** The PDF this module prints for a record when the server's action names no document. */
fun defaultPdfDoc(module: String): String? = when (module) {
    "payments" -> "receipt"; "invoices" -> "invoice"; "certificates" -> "certificate"; "payslips" -> "salary-slip"
    "students" -> "id-card"; "staff", "teachers" -> "staff-id-card"
    else -> null
}

fun pdfPath(module: String, a: ModuleAction, id: String, row: JsonObject?): String =
    a.url?.replace("{id}", id) ?: "pdf/${a.doc ?: defaultPdfDoc(module) ?: module}/$id"

/** Message + per-field errors from a failed API call. */
data class ApiFailure(val message: String, val fields: Map<String, List<String>> = emptyMap(), val code: Int = 0)

fun AppContainer.failure(t: Throwable): ApiFailure {
    if (t is HttpException) {
        val body = runCatching { t.response()?.errorBody()?.string() }.getOrNull()
        val parsed = body?.let { runCatching { json.decodeFromString(ApiError.serializer(), it) }.getOrNull() }
        val msg = parsed?.message ?: when (t.code()) {
            423 -> "This record is locked and can't be changed."
            403 -> "You are not allowed to do this."
            404 -> "Not found."
            else -> "Server error (${t.code()})."
        }
        return ApiFailure(if (t.code() == 423 && parsed?.message == null) msg else msg, parsed?.errors ?: emptyMap(), t.code())
    }
    return ApiFailure(errorMessage(t))
}

// ---------- shared small UI ----------

@Composable
fun LockBadge(small: Boolean = false) {
    Surface(color = Color(0xFFC62828).copy(alpha = 0.12f), shape = RoundedCornerShape(50)) {
        Row(Modifier.padding(horizontal = if (small) 8.dp else 10.dp, vertical = 3.dp), verticalAlignment = Alignment.CenterVertically) {
            Icon(Icons.Outlined.Lock, "Locked", Modifier.size(if (small) 14.dp else 16.dp), tint = Color(0xFFC62828))
            Spacer(Modifier.width(4.dp))
            Text("Locked", color = Color(0xFFC62828), style = MaterialTheme.typography.labelMedium)
        }
    }
}

@Composable
fun LockBanner(reason: String?, note: String?) {
    Card(colors = CardDefaults.cardColors(containerColor = Color(0xFFFFEBEE))) {
        Row(Modifier.fillMaxWidth().padding(14.dp), verticalAlignment = Alignment.Top) {
            Icon(Icons.Outlined.Lock, null, tint = Color(0xFFC62828))
            Spacer(Modifier.width(10.dp))
            Column {
                Text("Locked — permanent record", fontWeight = FontWeight.SemiBold, color = Color(0xFFB71C1C))
                Text(reason ?: "This record can no longer be edited or deleted.", style = MaterialTheme.typography.bodyMedium, color = Color(0xFF5D4037))
                if (!note.isNullOrBlank() && note != reason) Text(note, style = MaterialTheme.typography.bodySmall, color = Color(0xFF6D4C41))
            }
        }
    }
}

/** Downloads a server file (PDF/Excel/zip) and opens it; returns a launcher function. */
@Composable
fun rememberFileOpener(): (path: String, name: String) -> Unit {
    val c = LocalContainer.current
    val ctx = LocalContext.current
    val snack = LocalSnackbar.current
    val scope = rememberCoroutineScope()
    return remember {
        { path: String, name: String ->
            scope.launch {
                snack.currentSnackbarData?.dismiss()
                launch { snack.showSnackbar("Downloading $name…") }
                try {
                    val d = ServerFiles.download(c, path, name)
                    snack.currentSnackbarData?.dismiss()
                    val opened = ServerFiles.open(ctx, d)
                    val where = if (d.savedToDownloads) " Saved in Downloads/DAV School." else ""
                    if (!opened) {
                        val r = snack.showSnackbar("No app to open ${d.file.name}.$where", actionLabel = "Share")
                        if (r == SnackbarResult.ActionPerformed) ServerFiles.share(ctx, d)
                    } else if (where.isNotEmpty()) snack.showSnackbar(where.trim())
                } catch (e: CancellationException) { throw e } catch (e: Throwable) {
                    snack.currentSnackbarData?.dismiss()
                    snack.showSnackbar(c.errorMessage(e))
                }
            }
            Unit
        }
    }
}
