package com.davdugda.sms.core.nav

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import com.davdugda.sms.core.data.MenuItem
import com.davdugda.sms.core.data.MetaRepository

/** Navigation routes of the app. */
object Routes {
    const val HOME = "home"
    fun module(key: String) = "m/$key"
    fun create(key: String) = "m/$key/new"
    fun record(key: String, id: String) = "r/$key/$id"
    fun edit(key: String, id: String) = "r/$key/$id/edit"
    fun history(key: String, id: String) = "r/$key/$id/history"
    fun screen(id: String, arg: String? = null) = "s/$id" + (arg?.let { "?arg=$it" } ?: "")
}

/** Custom (non-generic) screens the server menu can point at (docs/API_V2_CONTRACT.md, meta/menu). */
val CustomScreens = setOf(
    "dashboard", "attendance_take", "attendance_report", "staff_attendance", "timetable", "marks_entry", "report_cards",
    "fee_collect", "fee_reminders", "payroll_run", "promotion", "import", "backup", "audit", "settings", "messages",
    "notifications", "calendar", "search", "profile", "my_attendance", "results", "fee_generate", "reports",
)

/** In the Parent and Student apps these server modules open the friendlier read-only screens. */
private val familyScreens = mapOf(
    "homework" to Feature.Homework, "notices" to Feature.Notices, "events" to Feature.Events, "timetable" to Feature.Timetable,
    "leaves" to Feature.Leaves, "student_attendance" to Feature.MyAttendance, "exams" to Feature.Exams, "marks" to Feature.Results,
    "exam_schedules" to Feature.Exams, "invoices" to Feature.Fees, "payments" to Feature.Fees, "books" to Feature.Library,
    "book_issues" to Feature.Library, "transport_routes" to Feature.Transport,
)

/** Where a menu entry (module key or custom screen id) goes. */
fun routeFor(screen: String, appRole: String, meta: MetaRepository): String {
    if (appRole == "parent" || appRole == "student") familyScreens[screen]?.let { return it.route }
    return when (screen) {
        "dashboard" -> Routes.HOME
        "profile" -> Feature.Profile.route
        "messages" -> Feature.Messages.route
        "notices" -> if (meta.modules.containsKey("notices") && appRole != "parent" && appRole != "student") Routes.module("notices") else Feature.Notices.route
        "settings" -> if (meta.modules.containsKey("settings")) Routes.module("settings") else Routes.screen("settings")
        in CustomScreens -> Routes.screen(screen)
        else -> Routes.module(screen)
    }
}

fun routeFor(item: MenuItem, appRole: String, meta: MetaRepository): String =
    if ((item.type == "module" || item.type == "records") && meta.modules.containsKey(item.screen) && familyRoute(item.screen, appRole) == null) Routes.module(item.screen)
    else routeFor(item.screen, appRole, meta)

private fun familyRoute(screen: String, appRole: String) = if (appRole == "parent" || appRole == "student") familyScreens[screen] else null

/**
 * Menu entries with their routes, without duplicates. When the same id is both a custom screen and a module
 * (e.g. "staff_attendance": the marking screen and the records list), the first entry opens the screen and
 * a later one opens the generic records list.
 */
fun menuEntries(items: List<MenuItem>, appRole: String, meta: MetaRepository): List<Pair<MenuItem, String>> {
    val seen = mutableSetOf<String>()
    return items.mapNotNull { m ->
        var r = routeFor(m, appRole, meta)
        if (r in seen && meta.modules.containsKey(m.screen) && familyRoute(m.screen, appRole) == null) r = Routes.module(m.screen)
        if (!seen.add(r)) null else m to r
    }
}

private val iconMap: Map<String, ImageVector> by lazy { mapOf(
    "person_add" to Icons.Outlined.PersonAdd,
    "how_to_reg" to Icons.Outlined.HowToReg,
    "school" to Icons.Outlined.School,
    "people" to Icons.Outlined.People,
    "groups" to Icons.Outlined.Groups,
    "group" to Icons.Outlined.Group,
    "person" to Icons.Outlined.Person,
    "family_restroom" to Icons.Outlined.FamilyRestroom,
    "badge" to Icons.Outlined.Badge,
    "co_present" to Icons.Outlined.CoPresent,
    "class" to Icons.Outlined.Class,
    "menu_book" to Icons.Outlined.MenuBook,
    "book" to Icons.Outlined.Book,
    "library_books" to Icons.Outlined.LibraryBooks,
    "local_library" to Icons.Outlined.LocalLibrary,
    "auto_stories" to Icons.Outlined.AutoStories,
    "event" to Icons.Outlined.Event,
    "event_note" to Icons.Outlined.EventNote,
    "calendar_month" to Icons.Outlined.CalendarMonth,
    "calendar_today" to Icons.Outlined.CalendarToday,
    "celebration" to Icons.Outlined.Celebration,
    "schedule" to Icons.Outlined.Schedule,
    "access_time" to Icons.Outlined.AccessTime,
    "fact_check" to Icons.Outlined.FactCheck,
    "checklist" to Icons.Outlined.Checklist,
    "event_available" to Icons.Outlined.EventAvailable,
    "beach_access" to Icons.Outlined.BeachAccess,
    "quiz" to Icons.Outlined.Quiz,
    "grade" to Icons.Outlined.Grade,
    "star" to Icons.Outlined.Star,
    "military_tech" to Icons.Outlined.MilitaryTech,
    "edit_note" to Icons.Outlined.EditNote,
    "assignment" to Icons.Outlined.Assignment,
    "assignment_turned_in" to Icons.Outlined.AssignmentTurnedIn,
    "description" to Icons.Outlined.Description,
    "article" to Icons.Outlined.Article,
    "workspace_premium" to Icons.Outlined.WorkspacePremium,
    "card_membership" to Icons.Outlined.CardMembership,
    "badge_outlined" to Icons.Outlined.Badge,
    "receipt" to Icons.Outlined.Receipt,
    "receipt_long" to Icons.Outlined.ReceiptLong,
    "currency_rupee" to Icons.Outlined.CurrencyRupee,
    "payments" to Icons.Outlined.Payments,
    "payment" to Icons.Outlined.Payment,
    "request_quote" to Icons.Outlined.RequestQuote,
    "discount" to Icons.Outlined.Discount,
    "percent" to Icons.Outlined.Percent,
    "account_balance" to Icons.Outlined.AccountBalance,
    "account_balance_wallet" to Icons.Outlined.AccountBalanceWallet,
    "savings" to Icons.Outlined.Savings,
    "trending_up" to Icons.Outlined.TrendingUp,
    "trending_down" to Icons.Outlined.TrendingDown,
    "inventory" to Icons.Outlined.Inventory,
    "inventory_2" to Icons.Outlined.Inventory2,
    "warehouse" to Icons.Outlined.Warehouse,
    "category" to Icons.Outlined.Category,
    "devices" to Icons.Outlined.Devices,
    "chair" to Icons.Outlined.Chair,
    "hotel" to Icons.Outlined.Hotel,
    "bed" to Icons.Outlined.Bed,
    "apartment" to Icons.Outlined.Apartment,
    "meeting_room" to Icons.Outlined.MeetingRoom,
    "directions_bus" to Icons.Outlined.DirectionsBus,
    "local_shipping" to Icons.Outlined.LocalShipping,
    "route" to Icons.Outlined.Route,
    "commute" to Icons.Outlined.Commute,
    "campaign" to Icons.Outlined.Campaign,
    "notifications" to Icons.Outlined.Notifications,
    "announcement" to Icons.Outlined.Announcement,
    "feedback" to Icons.Outlined.Feedback,
    "report" to Icons.Outlined.Report,
    "report_problem" to Icons.Outlined.ReportProblem,
    "support_agent" to Icons.Outlined.SupportAgent,
    "forum" to Icons.Outlined.Forum,
    "chat" to Icons.Outlined.Chat,
    "mail" to Icons.Outlined.Mail,
    "email" to Icons.Outlined.Email,
    "local_post_office" to Icons.Outlined.LocalPostOffice,
    "call" to Icons.Outlined.Call,
    "phone" to Icons.Outlined.Phone,
    "contact_phone" to Icons.Outlined.ContactPhone,
    "room_preferences" to Icons.Outlined.RoomPreferences,
    "settings" to Icons.Outlined.Settings,
    "tune" to Icons.Outlined.Tune,
    "history" to Icons.Outlined.History,
    "manage_history" to Icons.Outlined.ManageHistory,
    "upload_file" to Icons.Outlined.UploadFile,
    "file_upload" to Icons.Outlined.FileUpload,
    "backup" to Icons.Outlined.Backup,
    "cloud_download" to Icons.Outlined.CloudDownload,
    "search" to Icons.Outlined.Search,
    "dashboard" to Icons.Outlined.Dashboard,
    "home" to Icons.Outlined.Home,
    "bar_chart" to Icons.Outlined.BarChart,
    "insights" to Icons.Outlined.Insights,
    "assessment" to Icons.Outlined.Assessment,
    "analytics" to Icons.Outlined.Analytics,
    "folder" to Icons.Outlined.Folder,
    "attach_file" to Icons.Outlined.AttachFile,
    "photo" to Icons.Outlined.Photo,
    "image" to Icons.Outlined.Image,
    "upgrade" to Icons.Outlined.Upgrade,
    "trending_flat" to Icons.Outlined.TrendingFlat,
    "security" to Icons.Outlined.Security,
    "lock" to Icons.Outlined.Lock,
    "work" to Icons.Outlined.Work,
    "apps" to Icons.Outlined.Apps,
    "list" to Icons.Outlined.List,
    "print" to Icons.Outlined.Print,
    "science" to Icons.Outlined.Science,
    "subject" to Icons.Outlined.Subject,
    "topic" to Icons.Outlined.Topic,
    "account_circle" to Icons.Outlined.AccountCircle,
    "date_range" to Icons.Outlined.DateRange,
    "view_module" to Icons.Outlined.ViewModule,
    "folder_shared" to Icons.Outlined.FolderShared,
    "table_chart" to Icons.Outlined.TableChart,
    "view_list" to Icons.Outlined.ViewList,
    "fingerprint" to Icons.Outlined.Fingerprint,
    "sports" to Icons.Outlined.Sports,
    "work_history" to Icons.Outlined.WorkHistory,
    "receipt_outlined" to Icons.Outlined.Receipt,
    "person_search" to Icons.Outlined.PersonSearch,
    "qr_code" to Icons.Outlined.QrCode,
    "verified" to Icons.Outlined.Verified,
    "storefront" to Icons.Outlined.Storefront,
    "rule" to Icons.Outlined.Rule,
    "price_change" to Icons.Outlined.PriceChange,
) }

/** Server icon name (Material icon name such as "person_add") to an icon; falls back to the area's icon. */
fun iconFor(name: String?, key: String? = null): ImageVector =
    name?.let { iconMap[it.removePrefix("mdi-").replace('-', '_')] }
        ?: key?.let { k -> (areaOfModule(k) ?: areaOfScreen(k))?.icon }
        ?: Icons.Outlined.Apps

fun tintFor(key: String?): Color = key?.let { k -> (areaOfModule(k) ?: areaOfScreen(k))?.tint } ?: Color(0xFF546E7A)

/** Titles for custom screens (when the menu has none). */
fun screenTitle(id: String): String = when (id) {
    "attendance_take" -> "Take Attendance"; "attendance_report" -> "Attendance Report"; "staff_attendance" -> "Staff Attendance"
    "timetable" -> "Timetable"; "marks_entry" -> "Enter Marks"; "report_cards" -> "Report Cards"; "fee_collect" -> "Collect Fees"
    "fee_reminders" -> "Fee Reminders"; "fee_generate" -> "Generate Fees"; "reports" -> "Reports"; "payroll_run" -> "Run Payroll"; "promotion" -> "Promote Students"; "import" -> "Import from Excel"
    "backup" -> "Backup"; "audit" -> "Change History (Audit)"; "settings" -> "School Settings"; "notifications" -> "Notifications"
    "calendar" -> "Calendar"; "search" -> "Search"; "my_attendance" -> "Attendance"; "results" -> "Results"
    else -> id.replace('_', ' ').replaceFirstChar { it.uppercase() }
}
