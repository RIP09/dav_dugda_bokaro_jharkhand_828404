package com.davdugda.sms.modules.front_office

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Front Office: server modules and screens that belong to this folder. */
val FrontOfficeArea = ModuleArea(
    folder = "front_office",
    title = "Front Office",
    icon = Icons.Outlined.RoomPreferences,
    tint = Color(0xFF0097A7),
    keys = listOf("visitors", "postal", "call_logs"),
    screens = listOf(),
    classic = mapOf(),
)
