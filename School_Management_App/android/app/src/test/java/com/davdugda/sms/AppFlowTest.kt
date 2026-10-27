package com.davdugda.sms

import android.content.Context
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.semantics.SemanticsProperties
import androidx.compose.ui.test.*
import androidx.compose.ui.test.junit4.createAndroidComposeRule
import androidx.test.core.app.ApplicationProvider
import com.github.takahirom.roborazzi.captureRoboImage
import org.junit.Rule
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import org.robolectric.annotation.Config
import org.robolectric.annotation.GraphicsMode

/**
 * End-to-end UI smoke test, run once per app flavor (testSchoolDebugUnitTest, testParentDebugUnitTest, ...).
 * Starts the real app on Robolectric, signs in with that app's demo account against a running backend
 * (php artisan serve; override with -PtestApi=...), opens the main screens and saves screenshots to
 * app/build/screenshots/.
 */
@RunWith(RobolectricTestRunner::class)
@GraphicsMode(GraphicsMode.Mode.NATIVE)
@Config(sdk = [34], qualifiers = "w411dp-h891dp-xxhdpi", application = SmsApplication::class)
class AppFlowTest {
    @get:Rule val rule = createAndroidComposeRule<MainActivity>()

    private val role = BuildConfig.APP_ROLE

    private fun shot(name: String) {
        rule.waitForIdle()
        rule.onRoot().captureRoboImage("build/screenshots/${role}_$name.png")
    }

    private fun waitForText(text: String, timeout: Long = 30_000) {
        rule.waitUntil(timeout) { rule.onAllNodesWithText(text, substring = true).fetchSemanticsNodes().isNotEmpty() }
    }

    private fun openFeature(title: String, expect: String) {
        rule.onNode(hasScrollAction() and hasAnyDescendant(hasText("Quick access"))).performScrollToNode(hasText(title))
        rule.onAllNodes(hasText(title) and hasClickAction()).onFirst().performClick()
        waitForText(expect)
        Thread.sleep(300); rule.waitForIdle()
    }

    private fun back() {
        rule.onNodeWithContentDescription("Back").performClick()
        waitForText("Quick access")
    }

    @Test
    fun signInAndBrowse() {
        val ctx = ApplicationProvider.getApplicationContext<Context>()
        ctx.getSharedPreferences("dav_sms_session", Context.MODE_PRIVATE).edit()
            .putString("base_url", System.getProperty("sms.api") ?: "http://127.0.0.1:8000/api/v1/").commit()

        waitForText("Use demo account")
        shot("01_login")
        rule.onNodeWithText("Use demo account").performClick()
        rule.onNode(hasText("Sign in") and hasClickAction()).performClick()
        waitForText("Quick access")
        Thread.sleep(500); rule.waitForIdle()
        shot("02_dashboard")

        when (role) {
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
        }
        rule.onAllNodes(hasText("Notices") and SemanticsMatcher.expectValue(SemanticsProperties.Role, Role.Tab)).onFirst().performClick()
        waitForText("Diwali")
        shot("09_notices")
        rule.onAllNodes(hasText("Profile") and SemanticsMatcher.expectValue(SemanticsProperties.Role, Role.Tab)).onFirst().performClick()
        waitForText("Change password")
        shot("10_profile")
    }
}
