package com.davdugda.sms.core.data

import kotlinx.serialization.Serializable
import kotlinx.serialization.json.*

// ===== API v2: module registry, generic records, audit, notifications, search, charts (docs/API_V2_CONTRACT.md) =====

@Serializable
data class ModulePermissions(
    val view: Boolean = true,
    val create: Boolean = false,
    val update: Boolean = false,
    val delete: Boolean = false,
    val export: Boolean = false,
    val `import`: Boolean = false,
    val history: Boolean = false,
)

@Serializable data class FieldOption(val value: JsonElement = JsonNull, val label: String = "") {
    val key: String get() = value.asText()
}
/** depends_on: this picker's options are filtered by another form field (Section ← Class, Student ← Section). */
@Serializable data class RefDependency(val field: String, val key: String)
@Serializable data class FieldRef(val module: String, val labelField: String? = null, val dependsOn: RefDependency? = null)

/** One choice in a reference picker. [parents] holds the row's class_id / section_id so dependent pickers can filter. */
data class RefOption(val id: String, val label: String, val parents: Map<String, String> = emptyMap())

/**
 * The picker this field waits on: what the server says (ref.depends_on), or — for older servers — the same rule worked out
 * here: a Section/Subject waits on the Class picker, a Student on the Section (or Class) picker of the same form.
 */
fun FieldMeta.dependency(all: List<FieldMeta>): RefDependency? {
    val r = ref ?: return null
    r.dependsOn?.let { return it }
    val names = all.map { it.name }.toSet()
    return when (r.module) {
        "sections", "subjects" -> name.replace(Regex("(section|subject)_id$"), "class_id").takeIf { it != name && it in names }?.let { RefDependency(it, "class_id") }
        "students" -> {
            val p = name.removeSuffix("student_id")
            when {
                "${p}section_id" in names -> RefDependency("${p}section_id", "section_id")
                "${p}class_id" in names -> RefDependency("${p}class_id", "class_id")
                else -> null
            }
        }
        else -> null
    }
}

/** Options of a dependent picker for the chosen parent value (all options when there is no dependency). */
fun List<RefOption>.forParent(dep: RefDependency?, parentValue: String?): List<RefOption> =
    if (dep == null) this else if (parentValue.isNullOrBlank()) emptyList() else filter { it.parents[dep.key] == parentValue }

@Serializable
data class FieldMeta(
    val name: String,
    val label: String? = null,
    val type: String = "text",
    val required: Boolean = false,
    val list: Boolean = false,
    val filter: Boolean = false,
    val readonly: Boolean = false,
    val immutable: Boolean = false,
    val options: List<FieldOption>? = null,
    val ref: FieldRef? = null,
    val help: String? = null,
    val default: JsonElement? = null,
) {
    val title: String get() = label ?: name.replace('_', ' ').replaceFirstChar { it.uppercase() }
}

@Serializable
data class ModuleAction(
    val key: String,
    val label: String = key,
    val kind: String = "post",          // post | pdf
    val confirm: String? = null,
    val `when`: String? = null,         // e.g. "status!=admitted"
    val reason: Boolean = false,        // ask the user for a reason (also forced for "cancel")
    val needsReason: Boolean = false,   // same, as the server names it
    val inputs: List<FieldMeta> = emptyList(),   // extra fields asked before running (e.g. amount, mode)
    val doc: String? = null,            // pdf doc name (pdf/{doc}/{id}) when the server provides it
    val url: String? = null,            // or a ready URL / path relative to the API
)

@Serializable
data class ModuleMeta(
    val key: String,
    val title: String = key,
    val group: String? = null,
    val icon: String? = null,
    val labelField: String? = null,
    val subtitleFields: List<String> = emptyList(),
    val permissions: ModulePermissions = ModulePermissions(),
    val lockable: Boolean = false,
    val lockNote: String? = null,
    val fields: List<FieldMeta> = emptyList(),
    val actions: List<ModuleAction> = emptyList(),
)

@Serializable data class MenuItem(val key: String, val title: String = key, val icon: String? = null, val screen: String = key, val type: String? = null, val module: String? = null) {
    /** The server module a "module" entry opens. Records screens whose key is also a custom screen come as "module:<key>". */
    val moduleKey: String get() = module ?: screen.removePrefix("module:")
}
@Serializable data class MenuGroup(val group: String = "", val items: List<MenuItem> = emptyList())

@Serializable data class PageMeta(val total: Int = 0, val page: Int = 1, val perPage: Int = 25, val lastPage: Int = 1, val unread: Int = 0)
@Serializable data class RecordPage(val data: List<JsonObject> = emptyList(), val meta: PageMeta? = null, val message: String? = null)
@Serializable data class AnyResult(val data: JsonElement? = null, val message: String? = null)

@Serializable data class FieldChange(val old: JsonElement? = null, val new: JsonElement? = null)
@Serializable
data class AuditRow(
    val id: Int = 0,
    val userId: Int? = null,
    val userName: String? = null,
    val action: String = "",
    val module: String? = null,
    val recordId: JsonElement? = null,
    val recordTitle: String? = null,
    val changes: Map<String, FieldChange>? = null,
    val ip: String? = null,
    val createdAt: String? = null,
)
@Serializable data class AuditPage(val data: List<AuditRow> = emptyList(), val meta: PageMeta? = null)

@Serializable
data class AppNotification(
    val id: Int,
    val title: String = "",
    val body: String? = null,
    val module: String? = null,
    val recordId: JsonElement? = null,
    val readAt: String? = null,
    val createdAt: String? = null,
)
@Serializable data class NotificationPage(val data: List<AppNotification> = emptyList(), val meta: PageMeta? = null)

@Serializable data class SearchHit(val module: String = "", val id: JsonElement = JsonNull, val title: String = "", val subtitle: String? = null, val screen: String? = null)

@Serializable data class ChartCard(val label: String = "", val value: JsonElement = JsonNull, val icon: String? = null, val color: String? = null)
@Serializable data class ChartDataset(val label: String? = null, val data: List<Double?> = emptyList())
@Serializable data class Chart(val id: String = "", val title: String = "", val type: String = "bar", val labels: List<String> = emptyList(), val datasets: List<ChartDataset> = emptyList())
@Serializable data class DashboardCharts(val cards: List<ChartCard> = emptyList(), val charts: List<Chart> = emptyList())

@Serializable data class UploadResult(val path: String = "", val url: String? = null)
@Serializable data class ImportError(val row: Int = 0, val message: String = "")
@Serializable data class ImportResult(val created: Int = 0, val updated: Int = 0, val errors: List<ImportError> = emptyList())

// ---- small JSON helpers used by the generic screens ----
fun JsonElement?.asText(): String = when (this) {
    null, JsonNull -> ""
    is JsonPrimitive -> contentOrNull ?: ""
    is JsonArray -> joinToString(", ") { it.asText() }
    is JsonObject -> (this["name"] ?: this["title"] ?: this["label"])?.asText() ?: toString()
}
fun JsonObject.str(key: String): String? = this[key]?.takeIf { it !is JsonNull }?.asText()?.takeIf { it.isNotEmpty() }
fun JsonObject.bool(key: String): Boolean = (this[key] as? JsonPrimitive)?.let { it.booleanOrNull ?: (it.intOrNull?.let { n -> n != 0 }) ?: (it.contentOrNull == "true") } ?: false
fun JsonObject.idInt(): Int? = (this["id"] as? JsonPrimitive)?.let { it.intOrNull ?: it.contentOrNull?.toIntOrNull() }
val JsonObject.isLocked: Boolean get() = bool("_locked")
