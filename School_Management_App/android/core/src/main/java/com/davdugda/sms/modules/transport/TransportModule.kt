package com.davdugda.sms.modules.transport

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Transport: server modules and screens that belong to this folder. */
val TransportArea = ModuleArea(
    folder = "transport",
    title = "Transport",
    icon = Icons.Outlined.DirectionsBus,
    tint = Color(0xFFFFB300),
    keys = listOf("transport_routes", "vehicles"),
    screens = listOf(),
    classic = mapOf("transport_routes" to ("Routes & riders" to Feature.Transport)),
)
