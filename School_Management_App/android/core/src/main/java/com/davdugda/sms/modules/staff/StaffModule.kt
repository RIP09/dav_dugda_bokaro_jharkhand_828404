package com.davdugda.sms.modules.staff

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Staff: server modules and screens that belong to this folder. */
val StaffArea = ModuleArea(
    folder = "staff",
    title = "Staff",
    icon = Icons.Outlined.Badge,
    tint = Color(0xFF00897B),
    keys = listOf("staff"),
    screens = listOf(),
    classic = mapOf(),
)
