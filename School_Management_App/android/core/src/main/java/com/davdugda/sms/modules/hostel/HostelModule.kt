package com.davdugda.sms.modules.hostel

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Hostel: server modules and screens that belong to this folder. */
val HostelArea = ModuleArea(
    folder = "hostel",
    title = "Hostel",
    icon = Icons.Outlined.Hotel,
    tint = Color(0xFF7B1FA2),
    keys = listOf("hostels", "hostel_rooms", "hostel_allocations"),
    screens = listOf(),
    classic = mapOf(),
)
