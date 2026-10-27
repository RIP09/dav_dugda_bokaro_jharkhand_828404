package com.davdugda.sms.core.ui.screens

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

class HomeworkViewModel(c: AppContainer) : BaseViewModel(c) {
    val list = loadState<List<Homework>>()
    val subjects = mutableStateOf<List<Subject>>(emptyList())
    val submissions = mutableStateOf<List<HomeworkSubmission>?>(null)
    fun refresh(childId: Int?) = load(list, silent = true) { api.homework(studentId = childId).data }
    fun loadSubjects(classId: Int?) = act { subjects.value = classId?.let { api.subjects(it).data } ?: emptyList(); null }
    fun create(body: HomeworkCreate, after: () -> Unit) = act(onDone = after) { api.createHomework(body).message }
    fun delete(h: Homework, after: () -> Unit) = act(onDone = after) { api.deleteHomework(h.id).message }
    fun submit(h: Homework, text: String, after: () -> Unit) = act(onDone = after) { api.submitHomework(h.id, SubmitContent(text)).message }
    fun openSubmissions(h: Homework) = act { submissions.value = api.submissions(h.id).data; null }
    fun review(s: HomeworkSubmission, status: String, grade: String?, feedback: String?, h: Homework) = act(onDone = { openSubmissions(h) }) { api.reviewSubmission(s.id, ReviewSubmission(status, grade, feedback)).message }
}

@Composable
fun HomeworkScreen() {
    val vm = screenViewModel { HomeworkViewModel(it) }
    MessageEffect(vm)
    val user = LocalUser.current
    val child = LocalChild.current.value
    val canPost = user.isAdmin || user.isTeacher
    val scope = if (canPost) screenViewModel { ClassScope(it) } else null
    var posting by remember { mutableStateOf(false) }
    var open by remember { mutableStateOf<Homework?>(null) }
    LaunchedEffect(child) { vm.refresh(child) }

    Scaffold(floatingActionButton = { if (canPost) ExtendedFloatingActionButton(onClick = { posting = true }, icon = { Icon(Icons.Outlined.PostAdd, null) }, text = { Text("Give homework") }) }) { pad ->
        Box(Modifier.padding(pad)) {
            LoadContent(vm.list.value, { vm.refresh(child) }) { list ->
                ScreenList {
                    if (list.isEmpty()) item { EmptyState("No homework") }
                    items(list, key = { it.id }) { h ->
                        val sub = h.mySubmission
                        InfoCard(
                            h.title,
                            subtitle = listOfNotNull(h.subjectName, if (canPost) h.classLabel else h.teacherName).joinToString(" · "),
                            supporting = "Given ${shortDate(h.assignedOn)} · due ${prettyDate(h.dueDate)}" + if (canPost) " · ${h.submissionsCount} submitted" else "",
                            leading = { IconBadge(Icons.Outlined.MenuBook, Color(0xFF8E24AA)) },
                            trailing = {
                                when {
                                    sub != null -> StatusChip(sub.status)
                                    !canPost && (h.dueDate ?: "9") < today() -> StatusChip("absent", "Missed")
                                    !canPost -> StatusChip("pending", "To do")
                                }
                            },
                            onClick = { open = h; if (canPost) vm.openSubmissions(h) },
                        )
                    }
                }
            }
        }
    }
    open?.let { h ->
        var answer by remember(h.id) { mutableStateOf(h.mySubmission?.content ?: "") }
        AlertDialog(onDismissRequest = { open = null; vm.submissions.value = null }, title = { Text(h.title) }, text = {
            Column(Modifier.verticalScrollSafe(), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                Text("${h.subjectName ?: ""} · ${h.classLabel ?: ""} · due ${prettyDate(h.dueDate)}", style = MaterialTheme.typography.bodySmall)
                Text(h.description ?: "")
                h.mySubmission?.let { s ->
                    HorizontalDivider()
                    Text("Your submission: ${s.status.cap()}${s.grade?.let { " · Grade $it" } ?: ""}", fontWeight = FontWeight.SemiBold)
                    s.feedback?.let { Text("Teacher: $it") }
                }
                if (user.isStudent) FormField("Your answer / what you did", answer, { answer = it }, multiline = true)
                if (canPost) {
                    HorizontalDivider()
                    val subs = vm.submissions.value
                    Text("Submissions (${subs?.size ?: 0})", fontWeight = FontWeight.SemiBold)
                    subs?.forEach { s ->
                        Card { Column(Modifier.padding(10.dp)) {
                            Text(s.studentName ?: "", fontWeight = FontWeight.SemiBold)
                            Text(s.content ?: "", style = MaterialTheme.typography.bodySmall)
                            Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                                StatusChip(s.status)
                                if (s.status != "checked") TextButton(onClick = { vm.review(s, "checked", "A", "Good work", h) }) { Text("Mark checked") }
                                if (s.status != "redo") TextButton(onClick = { vm.review(s, "redo", null, "Please redo", h) }) { Text("Redo") }
                            }
                        } }
                    }
                }
            }
        }, confirmButton = {
            if (user.isStudent) Button(onClick = { vm.submit(h, answer) { open = null; vm.refresh(child) } }, enabled = answer.isNotBlank() && !vm.busy) { Text(if (h.mySubmission != null) "Resubmit" else "Submit") }
            else TextButton(onClick = { open = null; vm.submissions.value = null }) { Text("Close") }
        }, dismissButton = {
            if (canPost && (user.isAdmin || h.teacherId == user.id)) TextButton(onClick = { vm.delete(h) { open = null; vm.refresh(child) } }) { Text("Delete", color = MaterialTheme.colorScheme.error) }
            else if (user.isStudent) TextButton(onClick = { open = null }) { Text("Close") }
        })
    }
    if (posting && scope != null) {
        var classId by remember { mutableStateOf<Int?>(null) }
        var sectionId by remember { mutableStateOf<Int?>(null) }
        var subject by remember { mutableStateOf<Int?>(null) }
        var title by remember { mutableStateOf("") }
        var desc by remember { mutableStateOf("") }
        var due by remember { mutableStateOf("") }
        FormDialog("Give homework", confirmText = "Post", busy = vm.busy, onDismiss = { posting = false }, onConfirm = {
            val s = sectionId
            if (s == null || title.isBlank()) vm.message = "Pick a section and enter a title"
            else vm.create(HomeworkCreate(s, subject, title, desc.ifBlank { null }, due.ifBlank { null })) { posting = false; vm.refresh(child) }
        }) {
            ClassSectionPicker(scope.classes.value, classId, sectionId) { c, s -> if (c != classId) { vm.loadSubjects(c); subject = null }; classId = c; sectionId = s }
            Dropdown("Subject", vm.subjects.value.map { it.id to it.name }, subject, { subject = it })
            FormField("Title", title, { title = it })
            FormField("Details", desc, { desc = it }, multiline = true)
            DateField("Due date", due, { due = it })
        }
    }
}
