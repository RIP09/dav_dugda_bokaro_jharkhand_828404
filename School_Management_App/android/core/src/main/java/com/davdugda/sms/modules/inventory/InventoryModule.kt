package com.davdugda.sms.modules.inventory

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Inventory: server modules and screens that belong to this folder. */
val InventoryArea = ModuleArea(
    folder = "inventory",
    title = "Inventory",
    icon = Icons.Outlined.Inventory2,
    tint = Color(0xFF795548),
    keys = listOf("inventory_items", "stock_movements", "assets"),
    screens = listOf(),
    classic = mapOf(),
)
