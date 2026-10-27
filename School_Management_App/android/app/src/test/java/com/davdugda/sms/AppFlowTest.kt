package com.davdugda.sms

import android.content.Context
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.semantics.SemanticsProperties
import androidx.compose.ui.test.*
import androidx.compose.ui.test.junit4.createAndroidComposeRule
import androidx.test.core.app.ApplicationProvider
import com.github.takahirom.roborazzi.captureRoboImage
import org.json.JSONArray
import org.json.JSONObject
import org.junit.Rule
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import org.robolectric.annotation.Config
import org.robolectric.annotation.GraphicsMode
import java.net.HttpURLConnection
import java.net.URL

/**
 * End-to-end UI smoke test, run once per app flavor (testSchoolDebugUnitTest, testParentDebugUnitTest, ...).
 * Starts the real app on Robolectric, signs in with that app's demo account against a running backend
 * (php artisan serve; override with -PtestApi=...), opens the main screens and saves screenshots to
 * app/build/screenshots/. On a v2 server (meta/modules present) it also walks the generic module engine:
 * a module list, a record, a locked record, notifications and search.
 */
@RunWith(RobolectricTestRunner::class)
@GraphicsMode(GraphicsMode.Mode.NATIVE)
@Config(sdk = [34], qualifiers = "w411dp-h891dp-xxhdpi", application = SmsApplication::class)
class AppFlowTest {
    @get:Rule val rule = createAndroidComposeRule<MainActivity>()

    private val role = BuildConfig.APP_ROLE
    private val api = System.getProperty("sms.api") ?: "http://127.0.0.1:8000/api/v1/"
    private val ctx get() = ApplicationProvider.getApplicationContext<Context>()

    private fun shot(name: String) {
        rule.waitForIdle()
        rule.onRoot().captureRoboImage("build/screenshots/${role}_$name.png")
    }

    private fun waitForText(text: String, timeout: Long = 30_000) {
        try {
            rule.waitUntil(timeout) { rule.onAllNodesWithText(text, substring = true).fetchSemanticsNodes().isNotEmpty() }
        } catch (e: Throwable) {
            runCatching { shot("FAILED_waiting_for_" + text.replace(Regex("[^A-Za-z0-9]"), "_")) }
            throw e
        }
    }

    private fun settle(ms: Long = 400) { Thread.sleep(ms); rule.waitForIdle() }

    private fun openFeature(title: String, expect: String) {
        val grid = hasTestTag("dashboard_grid")
        var tries = 0
        while (true) {
            try {
                settle(500)
                rule.onNode(grid).performScrollToNode(hasText(title) and hasClickAction())
                rule.onAllNodes(hasText(title) and hasClickAction() and hasAnyAncestor(grid)).onFirst().performClick()
                break
            } catch (e: AssertionError) { if (++tries >= 4) throw e }
        }
        waitForText(expect)
        settle(300)
    }

    private fun back(expect: String? = null) {
        rule.onNodeWithContentDescription("Back").performClick()
        if (expect != null) waitForText(expect) else waitForHome()
    }

    private fun waitForHome() {
        rule.waitUntil(30_000) { rule.onAllNodes(hasTestTag("dashboard_grid")).fetchSemanticsNodes().isNotEmpty() }
    }

    // ---- tiny API client for the test itself (to learn menu titles and record names) ----
    private fun get(path: String): JSONObject {
        val token = ctx.getSharedPreferences("dav_sms_session", Context.MODE_PRIVATE).getString("token", null)
        val c = URL(api + path).openConnection() as HttpURLConnection
        c.setRequestProperty("Accept", "application/json")
        if (token != null) c.setRequestProperty("Authorization", "Bearer $token")
        c.connectTimeout = 15000; c.readTimeout = 30000
        val body = (if (c.responseCode < 400) c.inputStream else c.errorStream)?.bufferedReader()?.readText() ?: "{}"
        if (c.responseCode >= 400) throw IllegalStateException("GET $path -> ${c.responseCode}")
        return JSONObject(body)
    }

    private fun v2(): Boolean = runCatching { get("meta/modules").getJSONArray("data").length() > 0 }.getOrDefault(false)

    /** The menu title the server gives for a module key / screen id (null when this role has no such entry). */
    private fun menuTitle(screen: String): String? {
        val groups = get("meta/menu").getJSONArray("data")
        for (i in 0 until groups.length()) {
            val items = groups.getJSONObject(i).optJSONArray("items") ?: JSONArray()
            for (j in 0 until items.length()) { val it = items.getJSONObject(j); if (it.optString("screen") == screen || it.optString("key") == screen) return it.optString("title") }
        }
        return null
    }

    private fun firstRowTitle(module: String, query: String = ""): String? {
        val rows = get("records/$module?per_page=1&sort=-id$query").getJSONArray("data")
        return if (rows.length() == 0) null else rows.getJSONObject(0).optString("_title").ifBlank { null }
    }

    /** Opens a module from the dashboard tiles, then its newest record. */
    private fun openModuleRecord(module: String, prefix: String, expectInRecord: String, search: String? = null, onRecord: (() -> Unit)? = null): Boolean {
        val title = menuTitle(module) ?: return false
        val row = firstRowTitle(module, search?.let { "&q=" + java.net.URLEncoder.encode(it, "UTF-8") } ?: "") ?: return false
        openFeature(title, "record(s)")
        shot("${prefix}_list")
        if (search != null) { rule.onAllNodes(hasSetTextAction()).onFirst().performTextInput(search); settle(1500) }
        waitForText(row)
        rule.onAllNodes(hasText(row, substring = true) and hasClickAction()).onFirst().performClick()
        waitForText(expectInRecord)
        settle()
        shot("${prefix}_record")
        onRecord?.invoke()
        back("record(s)"); back()
        return true
    }

    @Test
    fun signInAndBrowse() {
        ctx.getSharedPreferences("dav_sms_session", Context.MODE_PRIVATE).edit().putString("base_url", api).commit()

        waitForText("Use demo account")
        shot("01_login")
        rule.onNodeWithText("Use demo account").performClick()
        rule.onNode(hasText("Sign in") and hasClickAction()).performClick()
        waitForText("Quick access")
        settle(1500)
        shot("02_dashboard")
        val isV2 = v2()

        if (!isV2) when (role) {
            "school" -> {
                openFeature("Students", "students"); shot("03_students"); back()
                openFeature("Fees", "Collected this month"); shot("04_fees"); back()
                openFeature("Classes & Subjects", "LKG"); shot("05_classes"); back()
                openFeature("Leave", "Approve"); shot("06_leave_approvals"); back()
            }
            "staff" -> {
                openFeature("Timetable", "Showing"); shot("03_timetable"); back()
                openFeature("Homework", "Given"); shot("04_homework"); back()
                openFeature("Take Attendance", "Pick a class"); shot("05_attendance"); back()
            }
            "parent" -> {
                openFeature("Attendance", "Overall attendance"); shot("03_attendance"); back()
                openFeature("Results", "Percentage"); shot("04_results"); back()
                openFeature("Fees", "Payments / receipts"); shot("05_fees"); back()
            }
            "student" -> {
                openFeature("Homework", "Given"); shot("03_homework"); back()
                openFeature("Results", "Percentage"); shot("04_results"); back()
                openFeature("Timetable", "Mon"); shot("05_timetable"); back()
            }
        } else {
            // ---------------- API v2: generic module engine ----------------
            if (rule.onAllNodesWithText("At a glance").fetchSemanticsNodes().isNotEmpty() || runCatching { get("dashboard/charts") }.isSuccess) {
                runCatching {
                    rule.onNode(hasTestTag("dashboard_grid")).performScrollToNode(hasText("At a glance"))
                    rule.onNode(hasTestTag("dashboard_grid")).performTouchInput { swipeUp(startY = bottom * 0.9f, endY = top + bottom * 0.15f) }
                    settle(800); shot("03_charts")
                    rule.onNode(hasTestTag("dashboard_grid")).performScrollToIndex(0)
                }
            }
            when (role) {
                "school" -> {
                    check(openModuleRecord("students", "04_students", "History") {
                        // real edit: change the roll number, check the history shows old -> new, then put it back
                        fun editRoll(to: String?): String {
                            rule.onNode(hasText("Edit") and hasClickAction()).performClick()
                            waitForText("Save changes"); settle()
                            val field = rule.onNode(hasSetTextAction() and hasText("Roll no", substring = true))
                            val old = field.fetchSemanticsNode().config.getOrElseNullable(SemanticsProperties.EditableText) { null }?.text.orEmpty()
                            field.performTextReplacement(to ?: ((old.toIntOrNull() ?: 0) + 100).toString())
                            if (to == null) shot("04_students_edit_form")
                            rule.onNode(hasText("Save changes") and hasClickAction()).performScrollTo().performClick()
                            waitForText("History"); settle(800)
                            return old
                        }
                        val original = editRoll(null)
                        rule.onNode(hasText("History") and hasClickAction()).performClick()
                        waitForText("Roll no", timeout = 20_000); settle(); shot("04_students_history")
                        back("History")
                        editRoll(original)
                    }) { "students module missing" }
                    // a permanent record: fee receipts are always locked
                    check(openModuleRecord("payments", "05_receipt", "Locked")) { "payments module missing" }
                    openModuleRecord("certificates", "06_certificate", "Certificate")
                    openModuleRecord("enquiries", "07_enquiry", "Edit")
                    menuTitle("audit")?.let { openFeature(it, "can't be edited"); settle(800); shot("08_audit"); back() }
                    menuTitle("reports")?.let { openFeature(it, "Tap PDF"); shot("09_reports"); back() }
                }
                "staff" -> {
                    openModuleRecord("homework", "04_homework", "Homework") || openModuleRecord("students", "04_students", "Students")
                    menuTitle("marks_entry")?.let { openFeature(it, "Exam"); shot("05_marks"); back() }
                    openModuleRecord("marks", "06_marks", "Marks")
                }
                "parent", "student" -> {
                    menuTitle("homework")?.let { openFeature(it, "Given"); shot("04_homework"); back() }
                    openModuleRecord("certificates", "05_certificate", "Certificate")
                    menuTitle("complaints")?.let { openFeature(it, "record(s)"); shot("06_complaints"); back() }
                }
            }
            // notifications + global search
            rule.onNodeWithContentDescription("Notifications").performClick()
            rule.waitUntil(30_000) { rule.onAllNodesWithText("unread", substring = true).fetchSemanticsNodes().isNotEmpty() || rule.onAllNodesWithText("caught up", substring = true).fetchSemanticsNodes().isNotEmpty() }
            settle(); shot("09_notifications")
            rule.onNodeWithContentDescription("Back").performClick(); waitForHome()
            if (role == "school" || role == "staff") {
                rule.onNodeWithContentDescription("Search").performClick()
                waitForText("Type at least")
                rule.onNode(hasSetTextAction()).performTextInput("kumar")
                rule.waitUntil(30_000) { rule.onAllNodesWithText("Type at least", substring = true).fetchSemanticsNodes().isEmpty() && rule.onAllNodes(hasClickAction() and hasText("Kumar", substring = true, ignoreCase = true)).fetchSemanticsNodes().isNotEmpty() }
                settle(); shot("10_search")
                rule.onNodeWithContentDescription("Back").performClick(); waitForHome()
            }
        }
        rule.onAllNodes(hasText("Notices") and SemanticsMatcher.expectValue(SemanticsProperties.Role, Role.Tab)).onFirst().performClick()
        waitForText("Diwali")
        shot("11_notices")
        rule.onAllNodes(hasText("Profile") and SemanticsMatcher.expectValue(SemanticsProperties.Role, Role.Tab)).onFirst().performClick()
        waitForText("Change password")
        shot("12_profile")
    }
}
