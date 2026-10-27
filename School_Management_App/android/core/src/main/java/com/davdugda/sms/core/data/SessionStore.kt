package com.davdugda.sms.core.data

import android.content.Context

/** Stores the sign-in token, cached profile and the API server address. */
class SessionStore(context: Context, private val defaultBaseUrl: String) {
    private val prefs = context.getSharedPreferences("dav_sms_session", Context.MODE_PRIVATE)

    var token: String?
        get() = prefs.getString("token", null)
        set(v) { prefs.edit().putString("token", v).apply() }

    var userJson: String?
        get() = prefs.getString("user", null)
        set(v) { prefs.edit().putString("user", v).apply() }

    var baseUrl: String
        get() = prefs.getString("base_url", null)?.takeIf { it.isNotBlank() } ?: defaultBaseUrl
        set(v) { prefs.edit().putString("base_url", normalize(v)).apply() }

    var selectedChildId: Int?
        get() = prefs.getInt("child_id", -1).takeIf { it > 0 }
        set(v) { prefs.edit().putInt("child_id", v ?: -1).apply() }

    fun clear() {
        prefs.edit().remove("token").remove("user").remove("child_id").apply()
    }

    companion object {
        /** Accepts "192.168.1.5:8000", "https://school.in" or a full ".../api/v1/" and returns a usable base URL. */
        fun normalize(input: String): String {
            var u = input.trim()
            if (!u.startsWith("http://") && !u.startsWith("https://")) u = "http://$u"
            u = u.trimEnd('/')
            if (!u.endsWith("/api/v1")) u = if (u.endsWith("/api")) "$u/v1" else "$u/api/v1"
            return "$u/"
        }
    }
}
