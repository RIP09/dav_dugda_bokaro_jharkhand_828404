package com.davdugda.sms.modules.auth

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Sign in: server modules and screens that belong to this folder. */
val AuthArea = ModuleArea(
    folder = "auth",
    title = "Sign in",
    icon = Icons.Outlined.Login,
    tint = Color(0xFF546E7A),
    keys = listOf(),
    screens = listOf(),
    classic = mapOf(),
)
