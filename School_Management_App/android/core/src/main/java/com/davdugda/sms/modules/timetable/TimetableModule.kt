package com.davdugda.sms.modules.timetable

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Timetable: server modules and screens that belong to this folder. */
val TimetableArea = ModuleArea(
    folder = "timetable",
    title = "Timetable",
    icon = Icons.Outlined.Schedule,
    tint = Color(0xFF1E88E5),
    keys = listOf("timetable"),
    screens = listOf("timetable"),
    classic = mapOf("timetable" to ("Week view" to Feature.Timetable)),
)
