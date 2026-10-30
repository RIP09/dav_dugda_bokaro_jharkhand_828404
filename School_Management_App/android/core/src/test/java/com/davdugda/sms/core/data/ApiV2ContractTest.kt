package com.davdugda.sms.core.data

import com.davdugda.sms.core.nav.CustomScreens
import com.davdugda.sms.modules.generic.displayValue
import com.davdugda.sms.modules.generic.editableFields
import com.davdugda.sms.modules.generic.formText
import com.davdugda.sms.modules.generic.rowTitle
import com.davdugda.sms.modules.generic.toJsonValue
import kotlinx.serialization.KSerializer
import kotlinx.serialization.json.*
import org.junit.Assert.assertTrue
import org.junit.Test
import java.io.File

/**
 * API v2 contract: decodes real responses of the v2 backend (every role) with the apps' exact Json setup
 * and models, and checks what the generic module engine relies on (menu targets exist, rows carry
 * _title/_locked, fee receipts are locked, every field value can be shown and put back into a form).
 * Fixtures: core/src/test/resources/fixtures_v2/<role>__<endpoint>.json (tools/capture_v2_fixtures.py).
 */
class ApiV2ContractTest {
    private val json = Json {
        ignoreUnknownKeys = true; explicitNulls = false; coerceInputValues = true; isLenient = true
        encodeDefaults = true; namingStrategy = JsonNamingStrategy.SnakeCase
    }

    private fun files(): List<File> {
        val url = javaClass.classLoader!!.getResource("fixtures_v2") ?: return emptyList()
        return File(url.toURI()).listFiles()!!.filter { it.extension == "json" }.sortedBy { it.name }
    }
    private fun role(f: File) = f.name.substringBefore("__")
    private fun ep(f: File) = f.name.substringAfter("__").removeSuffix(".json")
    private fun <T> dec(s: KSerializer<T>, f: File): T = json.decodeFromString(s, f.readText())

    @Test
    fun v2ResponsesDecodeAndFitTheEngine() {
        val all = files()
        assertTrue("v2 fixtures present", all.size > 100)
        val problems = mutableListOf<String>()
        var rowsChecked = 0
        val modulesByRole = mutableMapOf<String, Map<String, ModuleMeta>>()
        all.filter { ep(it) == "meta_modules" }.forEach { f ->
            val mods = dec(ApiList.serializer(ModuleMeta.serializer()), f).data
            if (mods.isEmpty()) problems += "${f.name}: no modules"
            modulesByRole[role(f)] = mods.associateBy { it.key }
        }
        for (f in all) {
            val r = role(f); val e = ep(f)
            try {
                when {
                    e == "meta_modules" -> {}
                    e == "meta_menu" -> {
                        val menu = dec(ApiList.serializer(MenuGroup.serializer()), f).data
                        val mods = modulesByRole[r].orEmpty()
                        menu.flatMap { it.items }.forEach { m ->
                            if (m.screen !in CustomScreens && !mods.containsKey(m.screen) && !(m.type == "module" && mods.containsKey(m.moduleKey))) problems += "${f.name}: menu item '${m.screen}' is neither a known screen nor one of this role's modules"
                        }
                    }
                    e.startsWith("records_") -> {
                        val key = e.removePrefix("records_")
                        val page = dec(RecordPage.serializer(), f)
                        val meta = modulesByRole[r]?.get(key)
                        page.data.forEach { row ->
                            rowsChecked++
                            if (row["_locked"] == null) problems += "${f.name}: row without _locked"
                            if (rowTitle(meta, row).isBlank()) problems += "${f.name}: row without a title"
                            if (row["_option"] == null) problems += "${f.name}: row without a picker label (_option)"
                            if (key == "sections" && !Regex("^(LKG|UKG|Class \\d+) – [A-Z]").containsMatchIn(row.str("_option").orEmpty())) problems += "${f.name}: section picker label without its class: ${row.str("_option")}"
                            meta?.fields?.forEach { fm -> displayValue(fm, row) }
                            meta?.let { m -> editableFields(m).forEach { fm -> toJsonValue(fm, formText(fm, row)) } }
                            if (key == "payments" && !row.isLocked) problems += "${f.name}: an issued receipt is not locked"
                            if (key == "audit" && (row.bool("_can_edit") || row.bool("_can_delete"))) problems += "${f.name}: audit row editable"
                        }
                    }
                    e.startsWith("record_") -> dec(ApiItem.serializer(JsonObject.serializer()), f).data ?: error("no data")
                    e.startsWith("history_") || e == "audit" -> dec(AuditPage.serializer(), f)
                    e == "notifications" -> dec(NotificationPage.serializer(), f)
                    e == "search" -> dec(ApiList.serializer(SearchHit.serializer()), f)
                    e == "dashboard_charts" -> {
                        val o = json.parseToJsonElement(f.readText()).jsonObject
                        json.decodeFromJsonElement(DashboardCharts.serializer(), (o["data"] as? JsonObject) ?: o)
                    }
                    e == "school" -> json.parseToJsonElement(f.readText()).jsonObject
                    e == "auth_me" -> dec(ApiItem.serializer(User.serializer()), f)
                    e == "dashboard" -> dec(ApiItem.serializer(Dashboard.serializer()), f)
                    e == "notices" -> dec(ApiList.serializer(Notice.serializer()), f)
                }
            } catch (t: Throwable) {
                problems += "${f.name}: ${t::class.simpleName}: ${t.message?.take(300)}"
            }
        }
        println("v2 fixtures: ${all.size} files, roles=${modulesByRole.keys}, rows checked=$rowsChecked, problems=${problems.size}")
        problems.forEach { println("  - $it") }
        assertTrue("API v2 contract problems:\n" + problems.joinToString("\n"), problems.isEmpty())
    }
}
