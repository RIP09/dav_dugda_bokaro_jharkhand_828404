package com.davdugda.sms.core.nav

import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector

/**
 * One management area of the school (Students, Fees, Hostel …). Each area lives in its own folder
 * under modules/<folder>/ and declares here which server modules (API v2 module keys) and custom
 * screens belong to it. The area's icon and colour are used for its tiles and lists.
 *
 * [classic] links a server module to a ready-made workflow screen of this app (for example the
 * "homework" list gets a button to open the homework desk that checks submissions).
 */
data class ModuleArea(
    val folder: String,
    val title: String,
    val icon: ImageVector,
    val tint: Color,
    val keys: List<String>,
    val screens: List<String> = emptyList(),
    val classic: Map<String, Pair<String, Feature>> = emptyMap(),
)
