package com.davdugda.sms.modules.search

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Search: server modules and screens that belong to this folder. */
val SearchArea = ModuleArea(
    folder = "search",
    title = "Search",
    icon = Icons.Outlined.Search,
    tint = Color(0xFF546E7A),
    keys = listOf(),
    screens = listOf("search"),
    classic = mapOf(),
)
