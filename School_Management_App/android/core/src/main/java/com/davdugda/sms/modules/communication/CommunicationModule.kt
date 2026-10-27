package com.davdugda.sms.modules.communication

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Communication: server modules and screens that belong to this folder. */
val CommunicationArea = ModuleArea(
    folder = "communication",
    title = "Communication",
    icon = Icons.Outlined.Campaign,
    tint = Color(0xFFE53935),
    keys = listOf("notices", "events", "complaints"),
    screens = listOf("messages", "notifications", "calendar"),
    classic = mapOf("notices" to ("Notice board" to Feature.Notices), "events" to ("Calendar view" to Feature.Events)),
)
