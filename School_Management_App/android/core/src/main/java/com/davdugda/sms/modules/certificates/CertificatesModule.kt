package com.davdugda.sms.modules.certificates

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Certificates: server modules and screens that belong to this folder. */
val CertificatesArea = ModuleArea(
    folder = "certificates",
    title = "Certificates",
    icon = Icons.Outlined.WorkspacePremium,
    tint = Color(0xFFFFA000),
    keys = listOf("certificates", "promotions"),
    screens = listOf(),
    classic = mapOf(),
)
