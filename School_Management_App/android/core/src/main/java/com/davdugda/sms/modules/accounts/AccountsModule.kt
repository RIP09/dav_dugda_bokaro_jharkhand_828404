package com.davdugda.sms.modules.accounts

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Accounts: server modules and screens that belong to this folder. */
val AccountsArea = ModuleArea(
    folder = "accounts",
    title = "Accounts",
    icon = Icons.Outlined.AccountBalance,
    tint = Color(0xFF00695C),
    keys = listOf("account_heads", "transactions"),
    screens = listOf(),
    classic = mapOf(),
)
