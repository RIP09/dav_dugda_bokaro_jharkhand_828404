package com.davdugda.sms.core.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewModelScope
import com.davdugda.sms.core.data.AppContainer
import com.davdugda.sms.core.data.LoginRequest
import com.davdugda.sms.core.data.SessionStore
import com.davdugda.sms.core.data.User
import com.davdugda.sms.core.ui.BaseViewModel
import com.davdugda.sms.core.ui.LocalContainer
import com.davdugda.sms.core.ui.screenViewModel
import kotlinx.coroutines.launch

class LoginViewModel(c: AppContainer) : BaseViewModel(c) {
    var error by mutableStateOf<String?>(null)
    var loading by mutableStateOf(false)

    fun login(id: String, password: String, onOk: (User) -> Unit) {
        if (id.isBlank() || password.isBlank()) { error = "Enter your email/phone and password"; return }
        viewModelScope.launch {
            loading = true; error = null
            try {
                val res = api.login(LoginRequest(id.trim(), password, c.config.appRole, "android-${c.config.appRole}")).data!!
                c.session.token = res.token
                c.session.userJson = c.json.encodeToString(User.serializer(), res.user)
                onOk(res.user)
            } catch (e: Throwable) {
                error = c.errorMessage(e)
            } finally { loading = false }
        }
    }
}

private val demo = mapOf(
    "school" to ("admin@davdugda.in" to "admin123"),
    "staff" to ("teacher@davdugda.in" to "teacher123"),
    "parent" to ("parent@davdugda.in" to "parent123"),
    "student" to ("student@davdugda.in" to "student123"),
)

@Composable
fun LoginScreen(onLoggedIn: (User) -> Unit) {
    val c = LocalContainer.current
    val vm = screenViewModel { LoginViewModel(it) }
    var id by remember { mutableStateOf("") }
    var pw by remember { mutableStateOf("") }
    var show by remember { mutableStateOf(false) }
    var server by remember { mutableStateOf(false) }
    val brand = MaterialTheme.colorScheme.primary
    val (who, hint) = when (c.config.appRole) {
        "school" -> "School administration" to "Admin email or phone"
        "staff" -> "Teachers & staff" to "Staff email or phone"
        "parent" -> "Parents & guardians" to "Registered mobile number or email"
        else -> "Students" to "Student email / login ID"
    }

    Column(Modifier.fillMaxSize().background(MaterialTheme.colorScheme.background).verticalScroll(rememberScrollState())) {
        Box(
            Modifier.fillMaxWidth().height(260.dp)
                .clip(RoundedCornerShape(bottomStart = 32.dp, bottomEnd = 32.dp))
                .background(Brush.verticalGradient(listOf(brand, brand.copy(alpha = 0.8f)))),
            contentAlignment = Alignment.Center,
        ) {
            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                Box(Modifier.size(78.dp).clip(RoundedCornerShape(22.dp)).background(Color.White), contentAlignment = Alignment.Center) {
                    Icon(Icons.Outlined.School, null, tint = brand, modifier = Modifier.size(46.dp))
                }
                Spacer(Modifier.height(14.dp))
                Text(c.config.appName, color = Color.White, style = MaterialTheme.typography.headlineSmall)
                Text("DAV Public School, Dugda", color = Color.White.copy(alpha = 0.9f))
                Text(who, color = Color.White.copy(alpha = 0.75f), style = MaterialTheme.typography.labelMedium)
            }
        }
        Column(Modifier.padding(24.dp), verticalArrangement = Arrangement.spacedBy(14.dp)) {
            Text("Sign in", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
            OutlinedTextField(
                id, { id = it }, Modifier.fillMaxWidth(), label = { Text(hint) }, singleLine = true,
                leadingIcon = { Icon(Icons.Outlined.Person, null) },
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email, imeAction = ImeAction.Next),
            )
            OutlinedTextField(
                pw, { pw = it }, Modifier.fillMaxWidth(), label = { Text("Password") }, singleLine = true,
                leadingIcon = { Icon(Icons.Outlined.Lock, null) },
                trailingIcon = { IconButton(onClick = { show = !show }) { Icon(if (show) Icons.Outlined.VisibilityOff else Icons.Outlined.Visibility, null) } },
                visualTransformation = if (show) VisualTransformation.None else PasswordVisualTransformation(),
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password, imeAction = ImeAction.Done),
            )
            vm.error?.let { Text(it, color = MaterialTheme.colorScheme.error, style = MaterialTheme.typography.bodyMedium) }
            Button(onClick = { vm.login(id, pw, onLoggedIn) }, Modifier.fillMaxWidth().height(52.dp), enabled = !vm.loading) {
                if (vm.loading) CircularProgressIndicator(Modifier.size(22.dp), color = MaterialTheme.colorScheme.onPrimary, strokeWidth = 2.dp)
                else Text("Sign in")
            }
            demo[c.config.appRole]?.let { (d, p) ->
                OutlinedButton(onClick = { id = d; pw = p }, Modifier.fillMaxWidth()) { Text("Use demo account") }
            }
            TextButton(onClick = { server = true }, Modifier.align(Alignment.CenterHorizontally)) {
                Icon(Icons.Outlined.Dns, null, Modifier.size(18.dp)); Spacer(Modifier.width(6.dp)); Text("Server settings")
            }
            Text("Forgot password? Please contact the school office.", style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant, textAlign = TextAlign.Center, modifier = Modifier.fillMaxWidth())
        }
    }
    if (server) ServerDialog(onDismiss = { server = false })
}

/** Lets the school point the app at its own server (e.g. https://sms.davdugda.in or 192.168.1.10:8000). */
@Composable
fun ServerDialog(onDismiss: () -> Unit) {
    val c = LocalContainer.current
    var url by remember { mutableStateOf(c.session.baseUrl) }
    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Server address") },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                Text("The address of the school's API server. Example: https://sms.davdugda.in or 192.168.1.10:8000", style = MaterialTheme.typography.bodySmall)
                OutlinedTextField(url, { url = it }, singleLine = true, modifier = Modifier.fillMaxWidth(), label = { Text("API URL") })
                Text("Will use: " + SessionStore.normalize(url.ifBlank { c.config.defaultBaseUrl }), style = MaterialTheme.typography.labelSmall)
            }
        },
        confirmButton = { TextButton(onClick = { c.session.baseUrl = url.ifBlank { c.config.defaultBaseUrl }; onDismiss() }) { Text("Save") } },
        dismissButton = {
            Row {
                TextButton(onClick = { url = c.config.defaultBaseUrl }) { Text("Reset") }
                TextButton(onClick = onDismiss) { Text("Cancel") }
            }
        },
    )
}
