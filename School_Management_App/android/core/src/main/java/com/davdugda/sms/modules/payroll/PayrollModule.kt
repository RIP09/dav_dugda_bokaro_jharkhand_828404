package com.davdugda.sms.modules.payroll

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Payroll: server modules and screens that belong to this folder. */
val PayrollArea = ModuleArea(
    folder = "payroll",
    title = "Payroll",
    icon = Icons.Outlined.Payments,
    tint = Color(0xFF6D4C41),
    keys = listOf("salary_structures", "payslips"),
    screens = listOf("payroll_run"),
    classic = mapOf(),
)
