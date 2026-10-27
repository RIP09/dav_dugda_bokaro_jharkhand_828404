package com.davdugda.sms.modules.teachers

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Teachers: server modules and screens that belong to this folder. */
val TeachersArea = ModuleArea(
    folder = "teachers",
    title = "Teachers",
    icon = Icons.Outlined.CoPresent,
    tint = Color(0xFF5E35B1),
    keys = listOf("teachers"),
    screens = listOf(),
    classic = mapOf(),
)
