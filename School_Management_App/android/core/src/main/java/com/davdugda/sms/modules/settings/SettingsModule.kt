package com.davdugda.sms.modules.settings

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Settings: server modules and screens that belong to this folder. */
val SettingsArea = ModuleArea(
    folder = "settings",
    title = "Settings",
    icon = Icons.Outlined.Settings,
    tint = Color(0xFF546E7A),
    keys = listOf("settings"),
    screens = listOf("settings"),
    classic = mapOf(),
)
