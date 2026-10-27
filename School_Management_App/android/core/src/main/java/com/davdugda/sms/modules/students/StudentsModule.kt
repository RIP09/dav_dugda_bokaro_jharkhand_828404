package com.davdugda.sms.modules.students

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Students: server modules and screens that belong to this folder. */
val StudentsArea = ModuleArea(
    folder = "students",
    title = "Students",
    icon = Icons.Outlined.School,
    tint = Color(0xFF3949AB),
    keys = listOf("students", "student_documents"),
    screens = listOf(),
    classic = mapOf("students" to ("Classic student list" to Feature.Students)),
)
