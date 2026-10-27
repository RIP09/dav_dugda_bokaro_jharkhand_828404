package com.davdugda.sms.core.nav

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import com.davdugda.sms.core.data.User

/** Every screen in the system. Each app (flavor) shows only the ones for its role. */
enum class Feature(val route: String, val title: String, val icon: ImageVector, val tint: Color) {
    Students("students", "Students", Icons.Outlined.School, Color(0xFF3949AB)),
    Staff("staff", "Staff", Icons.Outlined.Badge, Color(0xFF00897B)),
    Parents("parents", "Parents", Icons.Outlined.FamilyRestroom, Color(0xFFD81B60)),
    Classes("classes", "Classes & Subjects", Icons.Outlined.Class, Color(0xFF5E35B1)),
    Attendance("attendance", "Take Attendance", Icons.Outlined.FactCheck, Color(0xFF43A047)),
    StaffAttendance("staff_attendance", "Staff Attendance", Icons.Outlined.HowToReg, Color(0xFF00ACC1)),
    MyAttendance("my_attendance", "Attendance", Icons.Outlined.EventAvailable, Color(0xFF43A047)),
    Timetable("timetable", "Timetable", Icons.Outlined.Schedule, Color(0xFF1E88E5)),
    Exams("exams", "Exams", Icons.Outlined.Quiz, Color(0xFFF4511E)),
    Marks("marks", "Enter Marks", Icons.Outlined.EditNote, Color(0xFF6D4C41)),
    Results("results", "Results", Icons.Outlined.MilitaryTech, Color(0xFFFFA000)),
    Fees("fees", "Fees", Icons.Outlined.CurrencyRupee, Color(0xFF2E7D32)),
    Homework("homework", "Homework", Icons.Outlined.MenuBook, Color(0xFF8E24AA)),
    Notices("notices", "Notices", Icons.Outlined.Campaign, Color(0xFFE53935)),
    Events("events", "Events & Holidays", Icons.Outlined.Celebration, Color(0xFFFB8C00)),
    Library("library", "Library", Icons.Outlined.LocalLibrary, Color(0xFF6D4C41)),
    Transport("transport", "Transport", Icons.Outlined.DirectionsBus, Color(0xFFFFB300)),
    Leaves("leaves", "Leave", Icons.Outlined.BeachAccess, Color(0xFF039BE5)),
    Messages("messages", "Messages", Icons.Outlined.Forum, Color(0xFF7CB342)),
    Settings("settings", "School Settings", Icons.Outlined.Settings, Color(0xFF546E7A)),
    Profile("profile", "Profile", Icons.Outlined.AccountCircle, Color(0xFF546E7A)),
}

/** The screens each app shows. Staff app adapts to the person's job (teacher/accountant/librarian/other). */
fun featuresFor(appRole: String, user: User): List<Feature> = when (appRole) {
    "school" -> listOf(
        Feature.Students, Feature.Staff, Feature.Parents, Feature.Classes, Feature.Attendance, Feature.StaffAttendance,
        Feature.Timetable, Feature.Exams, Feature.Marks, Feature.Results, Feature.Fees, Feature.Homework, Feature.Notices,
        Feature.Events, Feature.Library, Feature.Transport, Feature.Leaves, Feature.Messages, Feature.Settings,
    )
    "staff" -> buildList {
        add(Feature.Timetable)
        when (user.role) {
            "teacher" -> addAll(listOf(Feature.Attendance, Feature.Marks, Feature.Homework, Feature.Students, Feature.Results, Feature.Exams))
            "accountant" -> addAll(listOf(Feature.Fees, Feature.Students))
            "librarian" -> addAll(listOf(Feature.Library, Feature.Students))
            else -> add(Feature.Students)
        }
        if (user.role != "librarian") add(Feature.Library)
        addAll(listOf(Feature.MyAttendance, Feature.Leaves, Feature.Notices, Feature.Events, Feature.Transport, Feature.Messages))
    }
    "parent" -> listOf(
        Feature.MyAttendance, Feature.Results, Feature.Fees, Feature.Homework, Feature.Timetable, Feature.Exams,
        Feature.Notices, Feature.Events, Feature.Leaves, Feature.Transport, Feature.Messages,
    )
    else -> listOf(
        Feature.Timetable, Feature.MyAttendance, Feature.Homework, Feature.Results, Feature.Exams, Feature.Fees,
        Feature.Library, Feature.Notices, Feature.Events, Feature.Leaves, Feature.Transport, Feature.Messages,
    )
}
