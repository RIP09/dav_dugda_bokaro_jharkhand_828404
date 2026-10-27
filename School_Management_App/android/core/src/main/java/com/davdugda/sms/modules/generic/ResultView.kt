package com.davdugda.sms.modules.generic

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextDecoration
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewModelScope
import com.davdugda.sms.core.data.*
import com.davdugda.sms.core.nav.*
import com.davdugda.sms.core.ui.*
import com.davdugda.sms.core.ui.components.*
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import kotlinx.serialization.json.*

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


/** Shows any JSON answer from the server as simple readable cards (used by reports and wizards). */
@Composable
fun JsonResultView(el: JsonElement?, depth: Int = 0) {
    when (el) {
        null, JsonNull -> {}
        is JsonPrimitive -> Text(el.content)
        is JsonArray -> Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
            el.take(300).forEach { item ->
                if (item is JsonObject) OutlinedCard(Modifier.fillMaxWidth()) { Column(Modifier.padding(12.dp)) { JsonObjectLines(item) } }
                else Text("• " + item.asText())
            }
            if (el.size > 300) Text("… and ${el.size - 300} more", color = MaterialTheme.colorScheme.outline)
        }
        is JsonObject -> Column(verticalArrangement = Arrangement.spacedBy(6.dp)) { JsonObjectLines(el, depth) }
    }
}

@Composable
private fun JsonObjectLines(o: JsonObject, depth: Int = 0) {
    o.forEach { (k, v) ->
        if (k.startsWith("_") || k.endsWith("_url") && v is JsonPrimitive && v.content.startsWith("http").not()) return@forEach
        when (v) {
            is JsonObject, is JsonArray -> if (depth < 2) {
                Text(k.cap(), style = MaterialTheme.typography.labelLarge, fontWeight = FontWeight.SemiBold, modifier = Modifier.padding(top = 4.dp))
                JsonResultView(v, depth + 1)
            }
            else -> Row {
                Text(k.cap() + ": ", style = MaterialTheme.typography.bodyMedium, color = MaterialTheme.colorScheme.onSurfaceVariant)
                Text(v.asText(), style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.Medium)
            }
        }
    }
}
