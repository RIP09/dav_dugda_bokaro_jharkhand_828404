package com.davdugda.sms.core.ui

import androidx.compose.material3.SnackbarHostState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.MutableState
import androidx.compose.runtime.staticCompositionLocalOf
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewmodel.compose.viewModel
import com.davdugda.sms.core.data.AppContainer
import com.davdugda.sms.core.data.User

val LocalContainer = staticCompositionLocalOf<AppContainer> { error("No AppContainer") }
val LocalUser = staticCompositionLocalOf<User> { error("No user") }
val LocalSnackbar = staticCompositionLocalOf { SnackbarHostState() }
/** Parent app: the child currently selected in the header switcher (student id). */
val LocalChild = staticCompositionLocalOf<MutableState<Int?>> { error("No child state") }

/** Navigate to any route of the app (see core/nav/Routes.kt). */
val LocalNavigate = staticCompositionLocalOf<(String) -> Unit> { {} }

/** Creates a ViewModel scoped to the current screen, handing it the app container. */
@Composable
inline fun <reified VM : ViewModel> screenViewModel(key: String? = null, crossinline create: (AppContainer) -> VM): VM {
    val c = LocalContainer.current
    return viewModel(key = key) { create(c) }
}

/** Shows a ViewModel's one-shot message in the shared snackbar. */
@Composable
fun MessageEffect(vm: BaseViewModel) {
    val snack = LocalSnackbar.current
    val msg = vm.message
    LaunchedEffect(msg) {
        if (msg != null) {
            snack.showSnackbar(msg)
            vm.message = null
        }
    }
}

/** Role helpers. */
val User.isAdmin get() = role == "admin"
val User.isTeacher get() = role == "teacher"
val User.isAccountant get() = role == "accountant"
val User.isLibrarian get() = role == "librarian"
val User.isParent get() = role == "parent"
val User.isStudent get() = role == "student"
val User.isStaffMember get() = role in listOf("teacher", "accountant", "librarian", "staff")
