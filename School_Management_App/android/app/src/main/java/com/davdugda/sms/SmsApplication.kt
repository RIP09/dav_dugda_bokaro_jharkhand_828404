package com.davdugda.sms

import android.app.Application
import com.davdugda.sms.core.AppConfig
import com.davdugda.sms.core.data.AppContainer

class SmsApplication : Application() {
    lateinit var container: AppContainer
        private set

    override fun onCreate() {
        super.onCreate()
        container = AppContainer(
            this,
            AppConfig(
                appRole = BuildConfig.APP_ROLE,
                appName = getString(R.string.app_name),
                brandColor = BuildConfig.BRAND_COLOR,
                defaultBaseUrl = BuildConfig.DEFAULT_BASE_URL,
                versionName = BuildConfig.VERSION_NAME,
            ),
        )
    }
}
