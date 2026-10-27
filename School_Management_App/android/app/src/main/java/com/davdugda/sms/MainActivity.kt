package com.davdugda.sms

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import com.davdugda.sms.core.AppConfig
import com.davdugda.sms.core.data.AppContainer
import com.davdugda.sms.core.nav.SmsApp

/**
 * Single activity for all four apps. The flavor (school / staff / parent / student) decides
 * APP_ROLE, the name, colour and icon at build time; the shared core module does the rest.
 */
class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        val container = (application as SmsApplication).container
        setContent { SmsApp(container) }
    }
}
