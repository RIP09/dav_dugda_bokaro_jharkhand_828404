package com.davdugda.sms.core.nav

import android.os.Handler
import android.os.Looper
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.outlined.ArrowBack
import androidx.compose.material.icons.automirrored.outlined.Logout
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.navigation.NavHostController
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import com.davdugda.sms.core.data.AppContainer
import com.davdugda.sms.core.data.User
import com.davdugda.sms.core.ui.*
import com.davdugda.sms.core.ui.components.Avatar
import com.davdugda.sms.core.ui.screens.*
import com.davdugda.sms.core.ui.theme.SmsTheme
import kotlinx.coroutines.launch

/** Root of every app: sign-in gate, theme, and the navigation shell. */
@Composable
fun SmsApp(container: AppContainer) {
    val brand = Color(container.config.brandColor)
    var user by remember {
        mutableStateOf(container.session.userJson?.let { runCatching { container.json.decodeFromString(User.serializer(), it) }.getOrNull() }
            ?.takeIf { container.session.token != null })
    }
    DisposableEffect(Unit) {
        container.onUnauthorized = { Handler(Looper.getMainLooper()).post { container.session.clear(); user = null } }
        onDispose { container.onUnauthorized = null }
    }
    val snackbar = remember { SnackbarHostState() }
    SmsTheme(brand) {
        CompositionLocalProvider(LocalContainer provides container, LocalSnackbar provides snackbar) {
            val u = user
            if (u == null) {
                LoginScreen(onLoggedIn = { user = it })
            } else {
                val child = remember(u.id) { mutableStateOf(container.session.selectedChildId?.takeIf { id -> u.children.any { it.id == id } } ?: u.children.firstOrNull()?.id) }
                CompositionLocalProvider(LocalUser provides u, LocalChild provides child) {
                    MainShell(
                        user = u,
                        onUserUpdated = { nu -> user = nu; container.session.userJson = container.json.encodeToString(User.serializer(), nu) },
                        onLogout = { user = null },
                    )
                }
            }
        }
    }
}

@Composable
private fun MainShell(user: User, onUserUpdated: (User) -> Unit, onLogout: () -> Unit) {
    val container = LocalContainer.current
    val features = remember(user) { featuresFor(container.config.appRole, user) }
    val nav = rememberNavController()
    val drawer = rememberDrawerState(DrawerValue.Closed)
    val scope = rememberCoroutineScope()
    val backStack by nav.currentBackStackEntryAsState()
    val route = backStack?.destination?.route ?: "home"
    val current = Feature.entries.firstOrNull { it.route == route }
    val bottom = listOf(
        Triple("home", "Home", Icons.Outlined.Home),
        Triple(Feature.Notices.route, "Notices", Icons.Outlined.Campaign),
        Triple(Feature.Messages.route, "Messages", Icons.Outlined.Forum),
        Triple(Feature.Profile.route, "Profile", Icons.Outlined.AccountCircle),
    )
    val isTop = bottom.any { it.first == route }
    val go: (String) -> Unit = { r ->
        nav.navigate(r) { popUpTo("home") { saveState = true }; launchSingleTop = true; restoreState = true }
    }

    ModalNavigationDrawer(
        drawerState = drawer,
        drawerContent = {
            ModalDrawerSheet {
                DrawerHeader(user)
                LazyColumn(Modifier.padding(horizontal = 12.dp)) {
                    item {
                        NavigationDrawerItem(label = { Text("Home") }, icon = { Icon(Icons.Outlined.Home, null) }, selected = route == "home",
                            onClick = { scope.launch { drawer.close() }; go("home") })
                    }
                    items(features) { f ->
                        NavigationDrawerItem(label = { Text(f.title) }, icon = { Icon(f.icon, null, tint = f.tint) }, selected = route == f.route,
                            onClick = { scope.launch { drawer.close() }; go(f.route) })
                    }
                    item {
                        HorizontalDivider(Modifier.padding(vertical = 8.dp))
                        NavigationDrawerItem(label = { Text("Profile & settings") }, icon = { Icon(Icons.Outlined.AccountCircle, null) }, selected = route == Feature.Profile.route,
                            onClick = { scope.launch { drawer.close() }; go(Feature.Profile.route) })
                        NavigationDrawerItem(label = { Text("Sign out") }, icon = { Icon(Icons.AutoMirrored.Outlined.Logout, null) }, selected = false,
                            onClick = { scope.launch { drawer.close(); runCatching { container.api.logout() }; container.session.clear(); onLogout() } })
                        Spacer(Modifier.height(24.dp))
                    }
                }
            }
        },
    ) {
        Scaffold(
            snackbarHost = { SnackbarHost(LocalSnackbar.current) },
            topBar = {
                TopAppBar(
                    title = {
                        Column {
                            Text(if (route == "home") container.config.appName else current?.title ?: "", fontWeight = FontWeight.SemiBold)
                            if (route == "home") Text("DAV Public School, Dugda", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }
                    },
                    navigationIcon = {
                        if (isTop) IconButton(onClick = { scope.launch { drawer.open() } }) { Icon(Icons.Outlined.Menu, "Menu") }
                        else IconButton(onClick = { nav.popBackStack() }) { Icon(Icons.AutoMirrored.Outlined.ArrowBack, "Back") }
                    },
                    actions = { if (user.isParent && user.children.size > 1) ChildSwitcher(user) },
                    colors = TopAppBarDefaults.topAppBarColors(containerColor = MaterialTheme.colorScheme.background),
                )
            },
            bottomBar = {
                if (isTop) NavigationBar {
                    bottom.forEach { (r, label, icon) ->
                        NavigationBarItem(selected = route == r, onClick = { go(r) }, icon = { Icon(icon, label) }, label = { Text(label) })
                    }
                }
            },
            containerColor = MaterialTheme.colorScheme.background,
        ) { pad ->
            Box(Modifier.padding(pad).fillMaxSize()) {
                AppNavHost(nav, features, onOpen = { nav.navigate(it) }, onUserUpdated = onUserUpdated, onLogout = onLogout)
            }
        }
    }
}

@Composable
private fun AppNavHost(nav: NavHostController, features: List<Feature>, onOpen: (String) -> Unit, onUserUpdated: (User) -> Unit, onLogout: () -> Unit) {
    NavHost(nav, startDestination = "home") {
        composable("home") { DashboardScreen(features, onOpen) }
        composable(Feature.Profile.route) { ProfileScreen(onUserUpdated, onLogout) }
        composable(Feature.Notices.route) { NoticesScreen() }
        composable(Feature.Messages.route) { MessagesScreen() }
        features.forEach { f ->
            if (f == Feature.Notices || f == Feature.Messages) return@forEach
            composable(f.route) { FeatureScreen(f) }
        }
    }
}

@Composable
fun FeatureScreen(f: Feature) {
    when (f) {
        Feature.Students -> StudentsScreen()
        Feature.Staff -> StaffScreen()
        Feature.Parents -> ParentsScreen()
        Feature.Classes -> ClassesScreen()
        Feature.Attendance -> TakeAttendanceScreen()
        Feature.StaffAttendance -> StaffAttendanceScreen()
        Feature.MyAttendance -> MyAttendanceScreen()
        Feature.Timetable -> TimetableScreen()
        Feature.Exams -> ExamsScreen()
        Feature.Marks -> MarksEntryScreen()
        Feature.Results -> ResultsScreen()
        Feature.Fees -> FeesScreen()
        Feature.Homework -> HomeworkScreen()
        Feature.Notices -> NoticesScreen()
        Feature.Events -> EventsScreen()
        Feature.Library -> LibraryScreen()
        Feature.Transport -> TransportScreen()
        Feature.Leaves -> LeavesScreen()
        Feature.Messages -> MessagesScreen()
        Feature.Settings -> SettingsScreen()
        Feature.Profile -> {}
    }
}

@Composable
private fun DrawerHeader(user: User) {
    val c = LocalContainer.current
    Column(Modifier.fillMaxWidth().padding(20.dp)) {
        Avatar(user.initials, MaterialTheme.colorScheme.primary, 56)
        Spacer(Modifier.height(10.dp))
        Text(user.name, style = MaterialTheme.typography.titleMedium)
        Text(listOfNotNull(user.staff?.designation ?: user.roleLabel, user.student?.classLabel).joinToString(" · "),
            style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
        Text(c.config.appName + " v" + c.config.versionName, style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.outline)
    }
}

@Composable
private fun ChildSwitcher(user: User) {
    val child = LocalChild.current
    val c = LocalContainer.current
    var open by remember { mutableStateOf(false) }
    val sel = user.children.firstOrNull { it.id == child.value }
    Box {
        AssistChip(onClick = { open = true }, label = { Text(sel?.displayName?.substringBefore(" ") ?: "Child") }, leadingIcon = { Icon(Icons.Outlined.ChildCare, null, Modifier.size(18.dp)) })
        DropdownMenu(expanded = open, onDismissRequest = { open = false }) {
            user.children.forEach { s ->
                DropdownMenuItem(text = { Text("${s.displayName} (${s.classLabel ?: ""})") }, onClick = {
                    child.value = s.id; c.session.selectedChildId = s.id; open = false
                })
            }
        }
    }
}
