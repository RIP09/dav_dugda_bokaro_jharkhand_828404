package com.davdugda.sms.modules.attendance

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Attendance: server modules and screens that belong to this folder. */
val AttendanceArea = ModuleArea(
    folder = "attendance",
    title = "Attendance",
    icon = Icons.Outlined.FactCheck,
    tint = Color(0xFF43A047),
    keys = listOf("student_attendance", "staff_attendance", "leaves"),
    screens = listOf("attendance_take", "staff_attendance"),
    classic = mapOf("student_attendance" to ("Take attendance" to Feature.Attendance), "staff_attendance" to ("Mark staff attendance" to Feature.StaffAttendance), "leaves" to ("Leave approvals" to Feature.Leaves)),
)
