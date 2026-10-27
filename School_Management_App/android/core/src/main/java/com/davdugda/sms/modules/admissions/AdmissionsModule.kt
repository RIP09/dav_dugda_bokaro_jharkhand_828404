package com.davdugda.sms.modules.admissions

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Admissions: server modules and screens that belong to this folder. */
val AdmissionsArea = ModuleArea(
    folder = "admissions",
    title = "Admissions",
    icon = Icons.Outlined.PersonAdd,
    tint = Color(0xFF00897B),
    keys = listOf("enquiries", "admissions"),
    screens = listOf(),
    classic = mapOf(),
)
