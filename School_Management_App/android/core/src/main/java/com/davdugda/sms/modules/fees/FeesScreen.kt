package com.davdugda.sms.modules.fees

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.davdugda.sms.core.data.*
import com.davdugda.sms.core.ui.*
import com.davdugda.sms.core.ui.components.*
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

class FeesViewModel(c: AppContainer) : BaseViewModel(c) {
    val invoices = loadState<List<Invoice>>()
    val payments = loadState<List<Payment>>()
    val summary = mutableStateOf<FeeSummary?>(null)
    val classes = mutableStateOf<List<SchoolClass>>(emptyList())
    val feeTypes = mutableStateOf<List<FeeType>>(emptyList())
    var status by mutableStateOf("due")
    private var initialised = false
    /** Parents and students see their full fee history by default; the office starts on dues. */
    fun initFor(manage: Boolean) { if (!initialised) { initialised = true; if (!manage) status = "all" } }
    var search by mutableStateOf("")

    fun refresh(manage: Boolean, childId: Int?) {
        load(invoices, silent = true) { api.invoices(status.takeIf { it != "all" }, if (manage) null else childId, search = search.ifBlank { null }).data }
        load(payments, silent = true) { api.payments().data }
        if (manage) act { summary.value = api.feeSummary().data; null }
    }
    fun loadForms() = act { classes.value = api.classes().data; feeTypes.value = api.feeTypes().data; null }
    fun pay(inv: Invoice, amount: Double, method: String, ref: String?, after: () -> Unit) = act(onDone = after) { api.pay(inv.id, PaymentCreate(amount, method, ref)).message }
    fun bill(body: InvoiceCreate, after: () -> Unit) = act(onDone = after) { api.createInvoices(body).message }
}

@Composable
fun FeesScreen() {
    val vm = screenViewModel { FeesViewModel(it) }
    MessageEffect(vm)
    val user = LocalUser.current
    val child = LocalChild.current.value
    val manage = user.isAdmin || user.isAccountant
    var tab by remember { mutableIntStateOf(0) }
    var paying by remember { mutableStateOf<Invoice?>(null) }
    var billing by remember { mutableStateOf(false) }
    var viewing by remember { mutableStateOf<Invoice?>(null) }
    vm.initFor(manage)
    val refresh = { vm.refresh(manage, child) }
    LaunchedEffect(child, vm.status) { refresh() }

    Scaffold(floatingActionButton = {
        if (manage) ExtendedFloatingActionButton(onClick = { billing = true; vm.loadForms() }, icon = { Icon(Icons.Outlined.ReceiptLong, null) }, text = { Text("Create invoice") })
    }) { pad ->
        Column(Modifier.padding(pad)) {
            vm.summary.value?.let { s ->
                Row(Modifier.padding(horizontal = 16.dp, vertical = 8.dp), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    StatCard("Collected this month", rupees(s.thisMonth), Icons.Outlined.Savings, Color(0xFF2E7D32), Modifier.weight(1f))
                    StatCard("Outstanding", rupees(s.due), Icons.Outlined.ReportProblem, Color(0xFFC62828), Modifier.weight(1f))
                }
            }
            TabRow(selectedTabIndex = tab, containerColor = MaterialTheme.colorScheme.background) {
                Tab(tab == 0, { tab = 0 }, text = { Text("Invoices") })
                Tab(tab == 1, { tab = 1 }, text = { Text("Payments / receipts") })
            }
            if (tab == 0) {
                Column(Modifier.padding(horizontal = 16.dp, vertical = 8.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    ChipRow(listOf("due" to "Due", "paid" to "Paid", "all" to "All"), vm.status) { vm.status = it }
                    if (manage) SearchField(vm.search, { vm.search = it; refresh() }, "Search student or invoice no.")
                }
                LoadContent(vm.invoices.value, refresh) { list ->
                    ScreenList {
                        if (!manage) {
                            val due = list.sumOf { it.balance }
                            item {
                                Card(colors = CardDefaults.cardColors(containerColor = if (due > 0) MaterialTheme.colorScheme.secondaryContainer else MaterialTheme.colorScheme.primaryContainer)) {
                                    Column(Modifier.fillMaxWidth().padding(16.dp)) {
                                        Text(if (due > 0) "Total due" else "All clear", style = MaterialTheme.typography.labelLarge)
                                        Text(rupees(due), style = MaterialTheme.typography.headlineSmall)
                                        if (due > 0) Text("Pay at the school accounts office (cash / UPI / cheque). Your receipt appears here once recorded.", style = MaterialTheme.typography.bodySmall)
                                    }
                                }
                            }
                        }
                        if (list.isEmpty()) item { EmptyState("No invoices") }
                        items(list, key = { it.id }) { inv ->
                            InfoCard(
                                inv.title,
                                subtitle = (if (manage) "${inv.student?.displayName ?: ""} · ${inv.student?.classLabel ?: ""}\n" else "") + "${inv.invoiceNo} · due ${prettyDate(inv.dueDate)}",
                                supporting = "Amount ${rupees(inv.amount - inv.discount)} · paid ${rupees(inv.paid)}" + if (inv.balance > 0) " · balance ${rupees(inv.balance)}" else "",
                                leading = { IconBadge(Icons.Outlined.CurrencyRupee, if (inv.status == "paid") Color(0xFF2E7D32) else Color(0xFFC62828)) },
                                trailing = { StatusChip(inv.status) },
                                onClick = { if (manage && inv.balance > 0) paying = inv else viewing = inv },
                            )
                        }
                    }
                }
            } else {
                LoadContent(vm.payments.value, refresh) { list ->
                    ScreenList {
                        if (list.isEmpty()) item { EmptyState("No payments yet") }
                        items(list, key = { it.id }) { p ->
                            InfoCard("${rupees(p.amount)} · ${p.method.uppercase()}", subtitle = "${p.invoiceTitle ?: ""}${p.studentName?.let { " · $it" } ?: ""}",
                                supporting = "Receipt ${p.receiptNo} · ${prettyDate(p.paidOn)}${p.reference?.let { " · Ref $it" } ?: ""}",
                                leading = { IconBadge(Icons.Outlined.Receipt, Color(0xFF2E7D32)) })
                        }
                    }
                }
            }
        }
    }
    viewing?.let { inv ->
        AlertDialog(onDismissRequest = { viewing = null }, title = { Text(inv.title) }, text = {
            Column {
                LabeledValue("Invoice", inv.invoiceNo); LabeledValue("Student", inv.student?.displayName); LabeledValue("Due date", prettyDate(inv.dueDate))
                LabeledValue("Amount", rupees(inv.amount)); if (inv.discount > 0) LabeledValue("Discount", rupees(inv.discount))
                LabeledValue("Paid", rupees(inv.paid)); LabeledValue("Balance", rupees(inv.balance)); LabeledValue("Status", inv.status.cap())
                inv.payments.forEach { p -> LabeledValue("Receipt ${p.receiptNo}", "${rupees(p.amount)} · ${p.method} · ${prettyDate(p.paidOn)}") }
            }
        }, confirmButton = { TextButton(onClick = { viewing = null }) { Text("Close") } })
    }
    paying?.let { inv ->
        var amount by remember { mutableStateOf(inv.balance.toLong().toString()) }
        var method by remember { mutableStateOf("cash") }
        var ref by remember { mutableStateOf("") }
        FormDialog("Record payment", confirmText = "Record", busy = vm.busy, onDismiss = { paying = null }, onConfirm = {
            val a = amount.toDoubleOrNull()
            if (a == null || a <= 0) vm.message = "Enter a valid amount" else vm.pay(inv, a, method, ref.ifBlank { null }) { paying = null; refresh() }
        }) {
            Text("${inv.student?.displayName ?: ""} · ${inv.title}", fontWeight = FontWeight.SemiBold)
            Text("Balance due: ${rupees(inv.balance)}")
            FormField("Amount (₹)", amount, { amount = it }, number = true)
            Dropdown("Method", listOf("cash" to "Cash", "upi" to "UPI", "card" to "Card", "bank" to "Bank transfer", "cheque" to "Cheque", "online" to "Online"), method, { method = it })
            FormField("Reference / UTR / cheque no.", ref, { ref = it })
        }
    }
    if (billing) {
        var classId by remember { mutableStateOf<Int?>(null) }
        var sectionId by remember { mutableStateOf<Int?>(null) }
        var feeType by remember { mutableStateOf<Int?>(null) }
        var title by remember { mutableStateOf("") }
        var amount by remember { mutableStateOf("") }
        var due by remember { mutableStateOf("") }
        FormDialog("Create invoice for a class", confirmText = "Create", busy = vm.busy, onDismiss = { billing = false }, onConfirm = {
            val a = amount.toDoubleOrNull()
            if (classId == null || title.isBlank() || a == null) vm.message = "Class, title and amount are required"
            else vm.bill(InvoiceCreate(classId = classId, sectionId = sectionId, feeTypeId = feeType, title = title, amount = a, dueDate = due.ifBlank { null })) { billing = false; refresh() }
        }) {
            ClassSectionPicker(vm.classes.value, classId, sectionId, allowAll = true) { c, s -> classId = c; sectionId = s }
            Dropdown("Fee type", vm.feeTypes.value.map { it.id to it.name }, feeType, { id ->
                feeType = id
                vm.feeTypes.value.firstOrNull { it.id == id }?.let { ft -> if (title.isBlank()) title = ft.name; if (amount.isBlank()) amount = ft.defaultAmount.toLong().toString() }
            })
            FormField("Title (e.g. Tuition Fee Q3)", title, { title = it })
            FormField("Amount per student (₹)", amount, { amount = it }, number = true)
            DateField("Due date", due, { due = it })
            Text("Creates one invoice for every active student in the chosen class/section.", style = MaterialTheme.typography.bodySmall)
        }
    }
}
