package com.davdugda.sms.modules.reports

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Reports: server modules and screens that belong to this folder. */
val ReportsArea = ModuleArea(
    folder = "reports",
    title = "Reports",
    icon = Icons.Outlined.Assessment,
    tint = Color(0xFF455A64),
    keys = listOf(),
    screens = listOf("attendance_report", "import", "backup", "audit"),
    classic = mapOf(),
)
