package com.davdugda.sms.modules.exams

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Exams: server modules and screens that belong to this folder. */
val ExamsArea = ModuleArea(
    folder = "exams",
    title = "Exams",
    icon = Icons.Outlined.Quiz,
    tint = Color(0xFFF4511E),
    keys = listOf("exams", "exam_schedules", "marks", "grades"),
    screens = listOf("marks_entry", "report_cards"),
    classic = mapOf("exams" to ("Date sheet view" to Feature.Exams), "marks" to ("Enter marks (grid)" to Feature.Marks)),
)
