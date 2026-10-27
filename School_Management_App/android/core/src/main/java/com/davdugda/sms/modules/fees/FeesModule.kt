package com.davdugda.sms.modules.fees

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Fees: server modules and screens that belong to this folder. */
val FeesArea = ModuleArea(
    folder = "fees",
    title = "Fees",
    icon = Icons.Outlined.CurrencyRupee,
    tint = Color(0xFF2E7D32),
    keys = listOf("fee_types", "fee_structures", "concessions", "invoices", "payments"),
    screens = listOf("fee_collect", "fee_reminders"),
    classic = mapOf("invoices" to ("Collect fees" to Feature.Fees), "payments" to ("Collect fees" to Feature.Fees)),
)
