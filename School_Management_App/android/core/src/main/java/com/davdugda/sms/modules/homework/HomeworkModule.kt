package com.davdugda.sms.modules.homework

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Homework: server modules and screens that belong to this folder. */
val HomeworkArea = ModuleArea(
    folder = "homework",
    title = "Homework",
    icon = Icons.Outlined.MenuBook,
    tint = Color(0xFF8E24AA),
    keys = listOf("homework"),
    screens = listOf(),
    classic = mapOf("homework" to ("Homework desk (submissions)" to Feature.Homework)),
)
