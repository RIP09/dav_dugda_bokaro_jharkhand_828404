package com.davdugda.sms.core.data

import android.content.Context
import com.davdugda.sms.core.AppConfig
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.JsonNamingStrategy
import okhttp3.Interceptor
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.HttpException
import retrofit2.Retrofit
import retrofit2.converter.kotlinx.serialization.asConverterFactory
import java.io.IOException
import java.util.concurrent.TimeUnit

/** Tiny manual DI container: one per app process. */
class AppContainer(context: Context, val config: AppConfig) {
    val appContext: Context = context.applicationContext
    val session = SessionStore(context.applicationContext, config.defaultBaseUrl)
    /** Server module registry + menu (API v2). */
    val meta = MetaRepository(this)

    val json = Json {
        ignoreUnknownKeys = true
        explicitNulls = false
        coerceInputValues = true
        isLenient = true
        encodeDefaults = true
        namingStrategy = JsonNamingStrategy.SnakeCase
    }

    /** Called when the server says the token is no longer valid (401). */
    var onUnauthorized: (() -> Unit)? = null

    private var cachedUrl: String? = null
    private var cachedApi: SmsApi? = null

    val api: SmsApi
        @Synchronized get() {
            val url = session.baseUrl
            if (cachedApi == null || url != cachedUrl) {
                cachedApi = build(url)
                cachedUrl = url
            }
            return cachedApi!!
        }

    private fun build(baseUrl: String): SmsApi {
        val auth = Interceptor { chain ->
            val req = chain.request().newBuilder()
                .header("Accept", "application/json")
                .apply { session.token?.let { header("Authorization", "Bearer $it") } }
                .build()
            val res = chain.proceed(req)
            if (res.code == 401 && session.token != null && !req.url.encodedPath.endsWith("auth/login")) onUnauthorized?.invoke()
            res
        }
        val log = HttpLoggingInterceptor().apply { level = HttpLoggingInterceptor.Level.BASIC }
        val client = OkHttpClient.Builder()
            .connectTimeout(20, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .addInterceptor(auth)
            .addInterceptor(log)
            .build()
        return Retrofit.Builder()
            .baseUrl(SessionStore.normalize(baseUrl))
            .client(client)
            .addConverterFactory(json.asConverterFactory("application/json".toMediaType()))
            .build()
            .create(SmsApi::class.java)
    }

    /** Turns any network/server failure into a sentence a person can read. */
    fun errorMessage(t: Throwable): String = when (t) {
        is HttpException -> {
            val body = runCatching { t.response()?.errorBody()?.string() }.getOrNull()
            val parsed = body?.let { runCatching { json.decodeFromString(ApiError.serializer(), it) }.getOrNull() }
            val first = parsed?.errors?.values?.firstOrNull()?.firstOrNull()
            first ?: parsed?.message ?: when (t.code()) {
                401 -> "Session expired. Please sign in again."
                403 -> "You are not allowed to do this."
                404 -> "Not found."
                423 -> "This record is locked and can't be changed."
                else -> "Server error (${t.code()})."
            }
        }
        is IOException -> t.message?.takeIf { it.endsWith(".") && !it.contains("Failed to connect") && !it.contains("Unable to resolve") } ?: "Cannot reach the school server. Check your internet or the server address in Settings."
        else -> t.message ?: "Something went wrong."
    }
}
