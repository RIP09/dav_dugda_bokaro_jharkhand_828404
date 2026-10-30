package com.davdugda.sms

import android.content.Context
import androidx.compose.ui.test.*
import androidx.compose.ui.test.junit4.createAndroidComposeRule
import androidx.test.core.app.ApplicationProvider
import com.github.takahirom.roborazzi.ExperimentalRoborazziApi
import com.github.takahirom.roborazzi.captureScreenRoboImage
import org.junit.Assume
import org.junit.Rule
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import org.robolectric.annotation.Config
import org.robolectric.annotation.GraphicsMode

/**
 * Deepanshu (27 Oct 2026): "when I adding students and choosing sections gets difficult because of not written for which
 * class this section appears". Walks School Admin → Students → Add, as a user would, and screenshots the Section picker
 * to app/build/screenshots/school_picker_*.png. Needs a running backend (-PtestApi=...).
 */
@RunWith(RobolectricTestRunner::class)
@GraphicsMode(GraphicsMode.Mode.NATIVE)
@Config(sdk = [34], qualifiers = "w411dp-h891dp-xxhdpi", application = SmsApplication::class)
class SectionPickerTest {
    @get:Rule val rule = createAndroidComposeRule<MainActivity>()
    private val api = System.getProperty("sms.api") ?: "http://127.0.0.1:8000/api/v1/"
    private val ctx get() = ApplicationProvider.getApplicationContext<Context>()

    @OptIn(ExperimentalRoborazziApi::class)
    private fun shot(name: String) { rule.waitForIdle(); Thread.sleep(300); rule.waitForIdle(); captureScreenRoboImage("build/screenshots/school_picker_$name.png") }
    private fun waitForText(text: String, timeout: Long = 30_000) = rule.waitUntil(timeout) { rule.onAllNodesWithText(text, substring = true).fetchSemanticsNodes().isNotEmpty() }
    private fun settle(ms: Long = 500) { Thread.sleep(ms); rule.waitForIdle() }

    @Test
    fun addStudentSectionPicker() {
        Assume.assumeTrue(BuildConfig.APP_ROLE == "school")
        ctx.getSharedPreferences("dav_sms_session", Context.MODE_PRIVATE).edit().putString("base_url", api).commit()
        waitForText("Use demo account")
        rule.onNodeWithText("Use demo account").performClick()
        rule.onNode(hasText("Sign in") and hasClickAction()).performClick()
        waitForText("Quick access"); settle(1500)

        val grid = hasTestTag("dashboard_grid")
        rule.onNode(grid).performScrollToNode(hasText("Students") and hasClickAction())
        rule.onAllNodes(hasText("Students") and hasClickAction() and hasAnyAncestor(grid)).onFirst().performClick()
        waitForText("record(s)"); settle(1500)
        shot("00_students_list")
        rule.onAllNodesWithText("Add", useUnmergedTree = true).onLast().performClick()
        waitForText("Student name"); settle(1500)
        shot("01_new_student_form")

        // What a user does first: tap Section (before the fix this listed "A", "A", "A", "B" … for every class)
        rule.onAllNodesWithText("Section", substring = true).onFirst().performClick()
        settle(800)
        shot("02_section_tapped_before_class")
        if (rule.onAllNodes(isPopup()).fetchSemanticsNodes().isNotEmpty()) { rule.onAllNodes(hasClickAction() and hasAnyAncestor(isPopup())).onFirst().performClick(); settle() }

        // Choose Class 6, then open Section again
        rule.onAllNodesWithText("Class *").onFirst().performClick()
        settle(800)
        rule.onAllNodes(hasText("Class 6") and hasClickAction() and hasAnyAncestor(isPopup())).onFirst().performClick()
        settle(800)
        rule.onAllNodesWithText("Section", substring = true).onFirst().performClick()
        settle(800)
        shot("03_section_list_after_choosing_class_6")
        val opts = rule.onAllNodes(hasClickAction() and hasAnyAncestor(isPopup())).fetchSemanticsNodes()
            .mapNotNull { n -> n.config.getOrElseNullable(androidx.compose.ui.semantics.SemanticsProperties.Text) { null }?.joinToString { t -> t.text } }
        println("SECTION OPTIONS AFTER CLASS 6: $opts")
        if (System.getProperty("sms.expectLabels") == "true") {
            check(opts.isNotEmpty() && opts.all { o: String -> o.startsWith("Class 6 – ") }) { "Section options should be Class 6's only, labelled with the class: $opts" }
        }
    }
}
