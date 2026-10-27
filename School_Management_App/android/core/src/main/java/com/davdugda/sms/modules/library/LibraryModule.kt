package com.davdugda.sms.modules.library

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.graphics.Color
import com.davdugda.sms.core.nav.Feature
import com.davdugda.sms.core.nav.ModuleArea

/** Library: server modules and screens that belong to this folder. */
val LibraryArea = ModuleArea(
    folder = "library",
    title = "Library",
    icon = Icons.Outlined.LocalLibrary,
    tint = Color(0xFF6D4C41),
    keys = listOf("books", "book_issues"),
    screens = listOf(),
    classic = mapOf("book_issues" to ("Issue / return desk" to Feature.Library), "books" to ("Issue / return desk" to Feature.Library)),
)
