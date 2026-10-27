package com.davdugda.sms.core.ui.theme

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Shapes
import androidx.compose.material3.Typography
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.compositeOver
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.foundation.shape.RoundedCornerShape

/** Each app passes its own brand colour; the rest of the palette is derived from it. */
@Composable
fun SmsTheme(brand: Color, content: @Composable () -> Unit) {
    val dark = isSystemInDarkTheme()
    val accent = Color(0xFFFFB300)
    val scheme = if (dark) darkColorScheme(
        primary = brand.copy(alpha = 1f).lighten(0.45f),
        onPrimary = Color(0xFF0B0B12),
        primaryContainer = brand.copy(alpha = 0.55f).compositeOver(Color(0xFF15151C)),
        onPrimaryContainer = Color.White,
        secondary = accent,
        secondaryContainer = accent.copy(alpha = 0.25f).compositeOver(Color(0xFF15151C)),
        background = Color(0xFF101116),
        surface = Color(0xFF15161C),
        surfaceVariant = Color(0xFF22232B),
        surfaceContainer = Color(0xFF1B1C23),
        surfaceContainerHigh = Color(0xFF23242C),
    ) else lightColorScheme(
        primary = brand,
        onPrimary = Color.White,
        primaryContainer = brand.copy(alpha = 0.12f).compositeOver(Color.White),
        onPrimaryContainer = brand.darken(0.35f),
        secondary = Color(0xFFE65100),
        secondaryContainer = accent.copy(alpha = 0.22f).compositeOver(Color.White),
        onSecondaryContainer = Color(0xFF5D3A00),
        background = Color(0xFFF6F7FB),
        surface = Color.White,
        surfaceVariant = Color(0xFFEFF1F6),
        surfaceContainer = Color(0xFFF1F3F8),
        surfaceContainerHigh = Color(0xFFEAEDF3),
        surfaceContainerLow = Color.White,
    )
    val base = Typography()
    val typography = base.copy(
        headlineSmall = base.headlineSmall.copy(fontWeight = FontWeight.Bold),
        titleLarge = base.titleLarge.copy(fontWeight = FontWeight.SemiBold),
        titleMedium = base.titleMedium.copy(fontWeight = FontWeight.SemiBold),
        labelLarge = TextStyle(fontSize = 14.sp, fontWeight = FontWeight.SemiBold, letterSpacing = 0.2.sp),
    )
    val shapes = Shapes(
        small = RoundedCornerShape(10.dp),
        medium = RoundedCornerShape(16.dp),
        large = RoundedCornerShape(24.dp),
    )
    MaterialTheme(colorScheme = scheme, typography = typography, shapes = shapes, content = content)
}

fun Color.lighten(f: Float) = Color(red + (1 - red) * f, green + (1 - green) * f, blue + (1 - blue) * f, alpha)
fun Color.darken(f: Float) = Color(red * (1 - f), green * (1 - f), blue * (1 - f), alpha)

object StatusColors {
    val present = Color(0xFF2E7D32)
    val absent = Color(0xFFC62828)
    val late = Color(0xFFEF6C00)
    val halfDay = Color(0xFF6A1B9A)
    val leave = Color(0xFF1565C0)
    fun of(status: String?): Color = when (status) {
        "present", "paid", "approved", "returned", "checked", "Pass" -> present
        "absent", "unpaid", "rejected", "overdue", "redo" -> absent
        "late", "partial", "pending", "submitted" -> late
        "half_day" -> halfDay
        "leave", "issued" -> leave
        else -> Color(0xFF607D8B)
    }
}
