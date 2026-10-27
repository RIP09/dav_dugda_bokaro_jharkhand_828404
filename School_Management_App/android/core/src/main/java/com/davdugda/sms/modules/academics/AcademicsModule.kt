package com.davdugda.sms.modules.academics

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Academics: server modules and screens that belong to this folder. */
val AcademicsArea = ModuleArea(
    folder = "academics",
    title = "Academics",
    icon = Icons.Outlined.Class,
    tint = Color(0xFF5E35B1),
    keys = listOf("academic_years", "classes", "sections", "subjects", "syllabus", "study_materials"),
    screens = listOf("promotion"),
    classic = mapOf("classes" to ("Classes & subjects view" to Feature.Classes)),
)
