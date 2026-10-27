package com.davdugda.sms.modules.library

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
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

// ================================ Library =====================================================

class LibraryViewModel(c: AppContainer) : BaseViewModel(c) {
    val books = loadState<List<Book>>()
    val issues = loadState<List<BookIssue>>()
    val members = mutableStateOf<List<Student>>(emptyList())
    var search by mutableStateOf("")
    init { refresh() }
    fun refresh() {
        load(books, silent = true) { api.books(search.ifBlank { null }).data }
        load(issues, silent = true) { api.issues().data }
    }
    fun loadMembers(q: String) = act { members.value = api.students(search = q.ifBlank { null }).data.take(30); null }
    fun addBook(b: BookCreate, done: () -> Unit) = act(onDone = { done(); refresh() }) { api.createBook(b).message }
    fun issue(bookId: Int, userId: Int, done: () -> Unit) = act(onDone = { done(); refresh() }) { api.issueBook(IssueCreate(bookId, userId)).message }
    fun giveBack(i: BookIssue) = act(onDone = ::refresh) { api.returnBook(i.id).message }
}

@Composable
fun LibraryScreen() {
    val vm = screenViewModel { LibraryViewModel(it) }
    MessageEffect(vm)
    val user = LocalUser.current
    val manage = user.isAdmin || user.isLibrarian
    var tab by remember { mutableIntStateOf(if (manage) 0 else 1) }
    var adding by remember { mutableStateOf(false) }
    var issuing by remember { mutableStateOf<Book?>(null) }
    Scaffold(floatingActionButton = { if (manage && tab == 0) ExtendedFloatingActionButton(onClick = { adding = true }, icon = { Icon(Icons.Outlined.LibraryAdd, null) }, text = { Text("Add book") }) }) { pad ->
        Column(Modifier.padding(pad)) {
            TabRow(selectedTabIndex = tab, containerColor = MaterialTheme.colorScheme.background) {
                Tab(tab == 0, { tab = 0 }, text = { Text("Catalogue") })
                Tab(tab == 1, { tab = 1 }, text = { Text(if (manage) "Issued books" else "My books") })
            }
            if (tab == 0) {
                Box(Modifier.padding(16.dp, 8.dp)) { SearchField(vm.search, { vm.search = it; vm.refresh() }, "Search title, author, ISBN") }
                LoadContent(vm.books.value, vm::refresh) { list ->
                    ScreenList {
                        if (list.isEmpty()) item { EmptyState("No books found") }
                        items(list, key = { it.id }) { b ->
                            InfoCard(b.title, subtitle = listOfNotNull(b.author, b.category).joinToString(" · "),
                                supporting = "${b.availableCopies} of ${b.totalCopies} available${b.rack?.let { " · Rack $it" } ?: ""}",
                                leading = { IconBadge(Icons.Outlined.Book, Color(0xFF6D4C41)) },
                                trailing = { if (manage && b.availableCopies > 0) TextButton(onClick = { issuing = b; vm.loadMembers("") }) { Text("Issue") } else if (b.availableCopies == 0) StatusChip("absent", "Out") })
                        }
                    }
                }
            } else {
                LoadContent(vm.issues.value, vm::refresh) { list ->
                    ScreenList {
                        if (list.isEmpty()) item { EmptyState("No books issued") }
                        items(list, key = { it.id }) { i ->
                            InfoCard(i.bookTitle ?: "", subtitle = (if (manage) "${i.memberName} · " else "") + "Issued ${shortDate(i.issuedOn)} · due ${prettyDate(i.dueOn)}",
                                supporting = if (i.fine > 0) "Fine ${rupees(i.fine)}" else i.returnedOn?.let { "Returned ${prettyDate(it)}" },
                                leading = { IconBadge(Icons.Outlined.Book, Color(0xFF6D4C41)) },
                                trailing = { if (manage && i.status != "returned") TextButton(onClick = { vm.giveBack(i) }) { Text("Return") } else StatusChip(i.status) })
                        }
                    }
                }
            }
        }
    }
    if (adding) {
        var title by remember { mutableStateOf("") }; var author by remember { mutableStateOf("") }; var isbn by remember { mutableStateOf("") }
        var cat by remember { mutableStateOf("") }; var copies by remember { mutableStateOf("1") }; var rack by remember { mutableStateOf("") }
        FormDialog("Add book", busy = vm.busy, onDismiss = { adding = false }, onConfirm = { vm.addBook(BookCreate(title, author.ifBlank { null }, isbn.ifBlank { null }, cat.ifBlank { null }, copies.toIntOrNull() ?: 1, rack.ifBlank { null })) { adding = false } }) {
            FormField("Title", title, { title = it }); FormField("Author", author, { author = it }); FormField("ISBN", isbn, { isbn = it })
            FormField("Category", cat, { cat = it }); FormField("Copies", copies, { copies = it }, number = true); FormField("Rack", rack, { rack = it })
        }
    }
    issuing?.let { b ->
        var q by remember { mutableStateOf("") }
        var member by remember { mutableStateOf<Int?>(null) }
        FormDialog("Issue \"${b.title}\"", confirmText = "Issue", busy = vm.busy, onDismiss = { issuing = null }, onConfirm = { member?.let { vm.issue(b.id, it) { issuing = null } } ?: run { vm.message = "Pick a student" } }) {
            FormField("Search student", q, { q = it; vm.loadMembers(it) })
            Dropdown("Student", vm.members.value.map { it.userId to "${it.displayName} (${it.classLabel ?: ""})" }, member, { member = it })
            Text("Due back in 14 days. Late fine is charged per day on return.", style = MaterialTheme.typography.bodySmall)
        }
    }
}

