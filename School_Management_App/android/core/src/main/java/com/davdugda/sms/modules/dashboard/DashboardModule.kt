package com.davdugda.sms.modules.dashboard

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Dashboard: server modules and screens that belong to this folder. */
val DashboardArea = ModuleArea(
    folder = "dashboard",
    title = "Dashboard",
    icon = Icons.Outlined.Dashboard,
    tint = Color(0xFF3949AB),
    keys = listOf(),
    screens = listOf("dashboard"),
    classic = mapOf(),
)
