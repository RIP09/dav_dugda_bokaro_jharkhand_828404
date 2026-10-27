package com.davdugda.sms.modules.profile

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Profile: server modules and screens that belong to this folder. */
val ProfileArea = ModuleArea(
    folder = "profile",
    title = "Profile",
    icon = Icons.Outlined.AccountCircle,
    tint = Color(0xFF546E7A),
    keys = listOf(),
    screens = listOf("profile"),
    classic = mapOf(),
)
