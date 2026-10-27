package com.davdugda.sms.modules.reports

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextDecoration
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewModelScope
import com.davdugda.sms.core.data.*
import com.davdugda.sms.core.nav.*
import com.davdugda.sms.core.ui.*
import com.davdugda.sms.core.ui.components.*
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import kotlinx.serialization.json.*

import com.davdugda.sms.modules.dashboard.*
import com.davdugda.sms.modules.auth.*
import com.davdugda.sms.modules.profile.*
import com.davdugda.sms.modules.students.*
import com.davdugda.sms.modules.parents.*
import com.davdugda.sms.modules.teachers.*
import com.davdugda.sms.modules.staff.*
import com.davdugda.sms.modules.academics.*
import com.davdugda.sms.modules.attendance.*
import com.davdugda.sms.modules.timetable.*
import com.davdugda.sms.modules.homework.*
import com.davdugda.sms.modules.exams.*
import com.davdugda.sms.modules.fees.*
import com.davdugda.sms.modules.library.*
import com.davdugda.sms.modules.transport.*
import com.davdugda.sms.modules.hostel.*
import com.davdugda.sms.modules.admissions.*
import com.davdugda.sms.modules.certificates.*
import com.davdugda.sms.modules.accounts.*
import com.davdugda.sms.modules.payroll.*
import com.davdugda.sms.modules.inventory.*
import com.davdugda.sms.modules.front_office.*
import com.davdugda.sms.modules.communication.*
import com.davdugda.sms.modules.reports.*
import com.davdugda.sms.modules.settings.*
import com.davdugda.sms.modules.search.*
import com.davdugda.sms.modules.generic.*


private data class ReportLink(val title: String, val text: String, val icon: androidx.compose.ui.graphics.vector.ImageVector, val module: String? = null, val export: String? = null, val route: String? = null)

/** All printable / downloadable reports in one place (PDF and Excel). */
@Composable
fun ReportsHubScreen() {
    val c = LocalContainer.current
    val user = LocalUser.current
    val navigate = LocalNavigate.current
    val openFile = rememberFileOpener()
    val has = { k: String -> c.meta.modules[k]?.permissions?.export == true }
    val links = listOfNotNull(
        ReportLink("Attendance register", "Month-wise, class-wise with percentages", Icons.Outlined.FactCheck, route = Routes.screen("attendance_report")),
        ReportLink("Students list", "All students with class, roll, parents", Icons.Outlined.School, "students", "export/students").takeIf { has("students") },
        ReportLink("Fee dues", "Unpaid and part-paid invoices", Icons.Outlined.ReportProblem, "invoices", "export/invoices?filter[status]=due").takeIf { has("invoices") },
        ReportLink("Fee collection", "Every receipt issued", Icons.Outlined.CurrencyRupee, "payments", "export/payments").takeIf { has("payments") },
        ReportLink("Income & expense", "Accounts day book", Icons.Outlined.AccountBalance, "transactions", "export/transactions").takeIf { has("transactions") },
        ReportLink("Staff list", "All staff with designation", Icons.Outlined.Badge, "staff", "export/staff").takeIf { has("staff") },
        ReportLink("Salary register", "Payslips", Icons.Outlined.Payments, "payslips", "export/payslips").takeIf { has("payslips") },
        ReportLink("Library issues", "Books issued and returned", Icons.Outlined.LocalLibrary, "book_issues", "export/book_issues").takeIf { has("book_issues") },
        ReportLink("Inventory", "Items and stock", Icons.Outlined.Inventory2, "inventory_items", "export/inventory_items").takeIf { has("inventory_items") },
        ReportLink("Visitors", "Front office register", Icons.Outlined.RoomPreferences, "visitors", "export/visitors").takeIf { has("visitors") },
        ReportLink("Change history", "Who changed what (audit)", Icons.Outlined.History, route = Routes.screen("audit")).takeIf { user.isAdmin },
    )
    ScreenList {
        item { Text("Tap PDF to print or Excel to open in a spreadsheet.", color = MaterialTheme.colorScheme.onSurfaceVariant) }
        items(links) { l ->
            InfoCard(l.title, subtitle = l.text, leading = { IconBadge(l.icon, tintFor(l.module ?: "reports")) },
                onClick = l.route?.let { r -> { navigate(r) } },
                trailing = {
                    if (l.export != null) Row {
                        val sep = if ('?' in l.export) "&" else "?"
                        TextButton(onClick = { openFile(l.export + sep + "format=pdf", "${l.module}_report.pdf") }) { Text("PDF") }
                        TextButton(onClick = { openFile(l.export + sep + "format=xlsx", "${l.module}_report.xlsx") }) { Text("Excel") }
                    }
                })
        }
    }
}
