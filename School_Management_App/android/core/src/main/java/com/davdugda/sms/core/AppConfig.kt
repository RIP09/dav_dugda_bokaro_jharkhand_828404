package com.davdugda.sms.core

/** Per-flavor configuration handed in by each app's MainActivity. */
data class AppConfig(
    val appRole: String,          // school | staff | parent | student
    val appName: String,
    val brandColor: Long,
    val defaultBaseUrl: String,
    val versionName: String,
)
