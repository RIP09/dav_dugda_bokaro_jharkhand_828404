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
import com.davdugda.sms.core.data.MenuGroup
import com.davdugda.sms.core.ui.components.EmptyState
import androidx.navigation.navArgument
import androidx.compose.ui.text.style.TextOverflow
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
    val args = backStack?.arguments
    val current = Feature.entries.firstOrNull { it.route == route }

    // API v2: server menu + module registry (falls back to the built-in screens on an older server)
    var menu by remember(user.id) { mutableStateOf<List<MenuGroup>>(emptyList()) }
    var unread by remember(user.id) { mutableStateOf(0) }
    var v2 by remember(user.id) { mutableStateOf(false) }
    var hasBell by remember(user.id) { mutableStateOf(false) }
    LaunchedEffect(user.id) {
        container.meta.clear()
        v2 = runCatching { container.meta.load() }.getOrDefault(false)
        menu = container.meta.menu
    }
    LaunchedEffect(route, v2) {
        if (v2) runCatching { container.api.notifications().let { it.meta?.unread ?: it.data.count { n -> n.readAt == null } } }
            .onSuccess { unread = it; hasBell = true }
    }

    val bottom = listOf(
        Triple("home", "Home", Icons.Outlined.Home),
        Triple(Feature.Notices.route, "Notices", Icons.Outlined.Campaign),
        Triple(Feature.Messages.route, "Messages", Icons.Outlined.Forum),
        Triple(Feature.Profile.route, "Profile", Icons.Outlined.AccountCircle),
    )
    val isTop = bottom.any { it.first == route }
    val go: (String) -> Unit = { r ->
        if (bottom.any { it.first == r }) nav.navigate(r) { popUpTo("home") { saveState = true }; launchSingleTop = true; restoreState = true }
        else nav.navigate(r) { launchSingleTop = true }
    }
    val title = when {
        route == "home" -> container.config.appName
        route.startsWith("m/") || route.startsWith("r/") -> {
            val key = args?.getString("key").orEmpty()
            val t = container.meta.modules[key]?.title ?: key.replace('_', ' ').replaceFirstChar { it.uppercase() }
            when {
                route.endsWith("/new") -> "Add · $t"
                route.endsWith("/edit") -> "Edit · $t"
                route.endsWith("/history") -> "History · $t"
                else -> t
            }
        }
        route.startsWith("s/") -> args?.getString("screen")?.let { s -> menu.flatMap { it.items }.firstOrNull { it.screen == s }?.title ?: screenTitle(s) } ?: ""
        else -> current?.title ?: ""
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
                    if (menu.isNotEmpty()) {
                        menu.forEach { g ->
                            val entries = g.items.filter { it.screen != "dashboard" && it.screen != "profile" }
                            if (entries.isEmpty()) return@forEach
                            item { if (g.group.isNotBlank()) Text(g.group.uppercase(), style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.outline, modifier = Modifier.padding(start = 16.dp, top = 12.dp, bottom = 4.dp)) }
                            items(menuEntries(entries, container.config.appRole, container.meta)) { (m, r) ->
                                NavigationDrawerItem(label = { Text(m.title) }, icon = { Icon(iconFor(m.icon, m.screen), null, tint = tintFor(m.screen)) },
                                    selected = currentPath(route, args) == r,
                                    badge = { if (m.screen == "notifications" && unread > 0) Badge { Text("$unread") } },
                                    onClick = { scope.launch { drawer.close() }; go(r) })
                            }
                        }
                    } else items(features) { f ->
                        NavigationDrawerItem(label = { Text(f.title) }, icon = { Icon(f.icon, null, tint = f.tint) }, selected = route == f.route,
                            onClick = { scope.launch { drawer.close() }; go(f.route) })
                    }
                    item {
                        HorizontalDivider(Modifier.padding(vertical = 8.dp))
                        NavigationDrawerItem(label = { Text("Profile & settings") }, icon = { Icon(Icons.Outlined.AccountCircle, null) }, selected = route == Feature.Profile.route,
                            onClick = { scope.launch { drawer.close() }; go(Feature.Profile.route) })
                        NavigationDrawerItem(label = { Text("Sign out") }, icon = { Icon(Icons.AutoMirrored.Outlined.Logout, null) }, selected = false,
                            onClick = { scope.launch { drawer.close(); runCatching { container.api.logout() }; container.session.clear(); container.meta.clear(); onLogout() } })
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
                            Text(title, fontWeight = FontWeight.SemiBold, maxLines = 1, overflow = TextOverflow.Ellipsis)
                            if (route == "home") Text(container.meta.schoolName ?: "DAV Public School, Dugda", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }
                    },
                    navigationIcon = {
                        if (isTop) IconButton(onClick = { scope.launch { drawer.open() } }) { Icon(Icons.Outlined.Menu, "Menu") }
                        else IconButton(onClick = { nav.popBackStack() }) { Icon(Icons.AutoMirrored.Outlined.ArrowBack, "Back") }
                    },
                    actions = {
                        if (user.isParent && user.children.size > 1) ChildSwitcher(user)
                        if (v2 && menu.any { g -> g.items.any { it.screen == "search" } }) IconButton(onClick = { go(Routes.screen("search")) }) { Icon(Icons.Outlined.Search, "Search") }
                        if (hasBell) {
                            IconButton(onClick = { go(Routes.screen("notifications")) }) {
                                BadgedBox(badge = { if (unread > 0) Badge { Text(if (unread > 99) "99+" else "$unread") } }) { Icon(Icons.Outlined.Notifications, "Notifications") }
                            }
                        }
                    },
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
                CompositionLocalProvider(LocalNavigate provides go) {
                    AppNavHost(nav, features, onOpen = go, onUserUpdated = onUserUpdated, onLogout = onLogout)
                }
            }
        }
    }
}

/** The concrete route currently shown (pattern with its arguments filled in). */
private fun currentPath(pattern: String, args: android.os.Bundle?): String {
    var p = pattern.substringBefore("?")
    listOf("key", "id", "screen").forEach { k -> args?.getString(k)?.let { p = p.replace("{$k}", it) } }
    return p
}

@Composable
private fun AppNavHost(nav: NavHostController, features: List<Feature>, onOpen: (String) -> Unit, onUserUpdated: (User) -> Unit, onLogout: () -> Unit) {
    NavHost(nav, startDestination = "home") {
        composable("home") { DashboardScreen(features, onOpen) }
        composable(Feature.Profile.route) { ProfileScreen(onUserUpdated, onLogout) }
        composable(Feature.Notices.route) { NoticesScreen() }
        composable(Feature.Messages.route) { MessagesScreen() }
        // Built-in workflow screens (every app registers all; the server decides what each role may do)
        Feature.entries.forEach { f ->
            if (f == Feature.Notices || f == Feature.Messages || f == Feature.Profile) return@forEach
            composable(f.route) { FeatureScreen(f) }
        }
        // Generic module engine (API v2): list, add, view, edit, history for every module
        composable("m/{key}") { e -> ModuleListScreen(e.arguments?.getString("key").orEmpty()) }
        composable("m/{key}/new") { e ->
            val key = e.arguments?.getString("key").orEmpty()
            RecordFormScreen(key, null) { id -> nav.popBackStack(); if (id.isNotEmpty()) nav.navigate(Routes.record(key, id)) }
        }
        composable("r/{key}/{id}") { e -> RecordDetailScreen(e.arguments?.getString("key").orEmpty(), e.arguments?.getString("id").orEmpty()) { nav.popBackStack() } }
        composable("r/{key}/{id}/edit") { e -> RecordFormScreen(e.arguments?.getString("key").orEmpty(), e.arguments?.getString("id")) { nav.popBackStack() } }
        composable("r/{key}/{id}/history") { e -> RecordHistoryScreen(e.arguments?.getString("key").orEmpty(), e.arguments?.getString("id").orEmpty()) }
        composable("s/{screen}?arg={arg}", arguments = listOf(navArgument("arg") { nullable = true; defaultValue = null })) { e ->
            CustomScreen(e.arguments?.getString("screen").orEmpty(), e.arguments?.getString("arg"))
        }
    }
}

/** Custom screens named by the server menu (docs/API_V2_CONTRACT.md). */
@Composable
fun CustomScreen(id: String, arg: String?) {
    val c = LocalContainer.current
    when (id) {
        "attendance_take" -> TakeAttendanceScreen()
        "attendance_report" -> AttendanceReportScreen()
        "staff_attendance" -> StaffAttendanceScreen()
        "my_attendance" -> MyAttendanceScreen()
        "timetable" -> TimetableScreen()
        "marks_entry" -> MarksEntryScreen()
        "report_cards", "results" -> ResultsScreen()
        "fee_collect" -> FeesScreen()
        "fee_reminders" -> FeeRemindersScreen()
        "fee_generate" -> FeeRemindersScreen(generateOnly = true)
        "reports" -> ReportsHubScreen()
        "payroll_run" -> PayrollRunScreen()
        "promotion" -> PromotionScreen()
        "import" -> ImportScreen(arg)
        "backup" -> BackupScreen()
        "audit" -> AuditScreen()
        "settings" -> if (c.meta.modules.containsKey("settings")) ModuleListScreen("settings") else SettingsScreen()
        "notifications" -> NotificationsScreen()
        "calendar" -> EventsScreen()
        "search" -> SearchScreen()
        "messages" -> MessagesScreen()
        else -> if (c.meta.modules.containsKey(id)) ModuleListScreen(id) else EmptyState("This screen is not available in this version of the app yet.")
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
        c.meta.principalName?.let { Text("Principal: $it", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant) }
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
