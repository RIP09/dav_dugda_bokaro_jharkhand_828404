package com.davdugda.sms.modules.parents

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Parents: server modules and screens that belong to this folder. */
val ParentsArea = ModuleArea(
    folder = "parents",
    title = "Parents",
    icon = Icons.Outlined.FamilyRestroom,
    tint = Color(0xFFD81B60),
    keys = listOf("parents"),
    screens = listOf(),
    classic = mapOf(),
)
