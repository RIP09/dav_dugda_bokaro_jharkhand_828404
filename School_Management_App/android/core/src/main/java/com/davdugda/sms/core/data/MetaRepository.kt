package com.davdugda.sms.core.data

import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import kotlinx.serialization.json.JsonObject

/**
 * Caches the server's module registry (meta/modules) and menu (meta/menu) for the signed-in user,
 * plus the option lists of reference fields (e.g. "Class" pickers). When the server is an older
 * Phase-1 server without the registry, [available] stays false and the apps use their built-in screens.
 */
class MetaRepository(private val c: AppContainer) {
    private val lock = Mutex()
    var modules: Map<String, ModuleMeta> = emptyMap(); private set
    var menu: List<MenuGroup> = emptyList(); private set
    var available: Boolean = false; private set
    var loaded: Boolean = false; private set
    var school: JsonObject? = null; private set
    private val refCache = mutableMapOf<String, List<Pair<String, String>>>()

    fun clear() { modules = emptyMap(); menu = emptyList(); available = false; loaded = false; refCache.clear() }

    suspend fun load(force: Boolean = false): Boolean = lock.withLock {
        if (loaded && !force) return available
        runCatching { school = c.api.schoolInfo().let { (it["data"] as? JsonObject) ?: it } }
        val mods = runCatching { c.api.metaModules().data }.getOrNull()
        val m = runCatching { c.api.metaMenu().data }.getOrNull()
        if (mods != null) modules = mods.associateBy { it.key }
        menu = m ?: emptyList()
        available = mods != null && mods.isNotEmpty()
        loaded = true
        available
    }

    suspend fun module(key: String): ModuleMeta {
        if (!loaded) load()
        modules[key]?.let { return it }
        load(force = true)
        return modules[key] ?: throw IllegalStateException("This section is not available on the school server yet.")
    }

    val principalName: String? get() = school?.str("principal_name") ?: school?.str("principal")
    val schoolName: String? get() = school?.str("name") ?: school?.str("school_name")

    /** id -> label list for a "ref" field (up to 500 records). */
    suspend fun refOptions(ref: FieldRef): List<Pair<String, String>> {
        refCache[ref.module]?.let { return it }
        val rows = c.api.records(ref.module, mapOf("per_page" to "500", "sort" to "id")).data
        val list = rows.map { r -> (r.str("id") ?: "") to (ref.labelField?.let { r.str(it) } ?: r.str("_title") ?: r.str("name") ?: ("#" + r.str("id"))) }
        refCache[ref.module] = list
        return list
    }

    fun invalidateRefs(module: String) { refCache.remove(module) }
}
