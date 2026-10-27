package com.davdugda.sms.core.data

import android.content.ActivityNotFoundException
import android.content.ContentValues
import android.content.Context
import android.content.Intent
import android.net.Uri
import android.os.Build
import android.os.Environment
import android.provider.MediaStore
import android.provider.OpenableColumns
import android.webkit.MimeTypeMap
import androidx.core.content.FileProvider
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.toRequestBody
import java.io.File
import java.io.IOException
import java.util.concurrent.TimeUnit

/**
 * Files from the server: PDFs (receipts, report cards, ID/admit cards, certificates, salary slips),
 * Excel/CSV exports, import templates and backups. They are downloaded with the sign-in token,
 * kept in the app's cache, copied to the phone's Downloads folder (Android 10+), and opened with
 * whatever app the phone has for that file type.
 */
object ServerFiles {
    private val http by lazy { OkHttpClient.Builder().connectTimeout(20, TimeUnit.SECONDS).readTimeout(120, TimeUnit.SECONDS).build() }

    /** "pdf/receipt/12" or "export/students?format=xlsx" (relative to the API) or a full https URL. */
    fun absoluteUrl(c: AppContainer, path: String): String {
        if (path.startsWith("http://") || path.startsWith("https://")) return path
        return SessionStore.normalize(c.session.baseUrl) + path.removePrefix("/").removePrefix("api/v1/")
    }

    /** The same URL with ?token= so a browser can open it too. */
    fun browserUrl(c: AppContainer, path: String): String {
        val u = absoluteUrl(c, path)
        val t = c.session.token ?: return u
        return u + (if ('?' in u) "&" else "?") + "token=" + Uri.encode(t)
    }

    data class Downloaded(val file: File, val mime: String, val savedToDownloads: Boolean)

    suspend fun download(c: AppContainer, path: String, suggestedName: String): Downloaded = withContext(Dispatchers.IO) {
        val req = Request.Builder().url(browserUrl(c, path))
            .apply { c.session.token?.let { header("Authorization", "Bearer $it") } }
            .header("Accept", "application/pdf, application/octet-stream, */*")
            .build()
        http.newCall(req).execute().use { res ->
            if (!res.isSuccessful) {
                val body = runCatching { res.body?.string() }.getOrNull()
                val msg = body?.let { runCatching { c.json.decodeFromString(ApiError.serializer(), it).message }.getOrNull() }
                throw IOException(msg ?: when (res.code) { 401 -> "Please sign in again."; 403 -> "You are not allowed to download this."; 404 -> "This file is not available on the server yet."; else -> "Download failed (${res.code})." })
            }
            val cd = res.header("Content-Disposition")
            val serverName = cd?.let { Regex("filename\\*?=\"?(?:UTF-8'')?([^\";]+)\"?").find(it)?.groupValues?.get(1) }
            val mime = res.body?.contentType()?.let { "${it.type}/${it.subtype}" } ?: "application/octet-stream"
            var name = (serverName ?: suggestedName).replace(Regex("[^A-Za-z0-9._-]"), "_")
            if (!name.contains('.')) MimeTypeMap.getSingleton().getExtensionFromMimeType(mime)?.let { name += ".$it" }
            val dir = File(c.appContext.cacheDir, "downloads").apply { mkdirs() }
            val f = File(dir, name)
            res.body!!.byteStream().use { input -> f.outputStream().use { input.copyTo(it) } }
            val saved = runCatching { saveToDownloads(c.appContext, f, mime) }.getOrDefault(false)
            Downloaded(f, mime, saved)
        }
    }

    private fun saveToDownloads(ctx: Context, f: File, mime: String): Boolean {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.Q) return false
        val values = ContentValues().apply {
            put(MediaStore.Downloads.DISPLAY_NAME, f.name)
            put(MediaStore.Downloads.MIME_TYPE, mime)
            put(MediaStore.Downloads.RELATIVE_PATH, Environment.DIRECTORY_DOWNLOADS + "/DAV School")
        }
        val uri = ctx.contentResolver.insert(MediaStore.Downloads.EXTERNAL_CONTENT_URI, values) ?: return false
        ctx.contentResolver.openOutputStream(uri)?.use { out -> f.inputStream().use { it.copyTo(out) } } ?: return false
        return true
    }

    fun uriFor(ctx: Context, f: File): Uri = FileProvider.getUriForFile(ctx, ctx.packageName + ".files", f)

    /** Opens the file in a viewer app. Returns false if the phone has no app for it. */
    fun open(ctx: Context, d: Downloaded): Boolean {
        val intent = Intent(Intent.ACTION_VIEW).setDataAndType(uriFor(ctx, d.file), d.mime)
            .addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION or Intent.FLAG_ACTIVITY_NEW_TASK)
        return try { ctx.startActivity(intent); true } catch (e: ActivityNotFoundException) { false }
    }

    fun share(ctx: Context, d: Downloaded) {
        val send = Intent(Intent.ACTION_SEND).setType(d.mime).putExtra(Intent.EXTRA_STREAM, uriFor(ctx, d.file))
            .addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
        ctx.startActivity(Intent.createChooser(send, "Share").addFlags(Intent.FLAG_ACTIVITY_NEW_TASK))
    }

    fun openUrl(ctx: Context, url: String) {
        runCatching { ctx.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url)).addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)) }
    }

    /** Reads a picked file (photo, Excel sheet …) into a multipart part. */
    fun part(ctx: Context, uri: Uri, partName: String): MultipartBody.Part {
        val cr = ctx.contentResolver
        val mime = cr.getType(uri) ?: "application/octet-stream"
        var name = "upload"
        cr.query(uri, arrayOf(OpenableColumns.DISPLAY_NAME), null, null, null)?.use { cur ->
            if (cur.moveToFirst()) cur.getString(0)?.let { name = it }
        }
        if (!name.contains('.')) MimeTypeMap.getSingleton().getExtensionFromMimeType(mime)?.let { name += ".$it" }
        val bytes = cr.openInputStream(uri)?.use { it.readBytes() } ?: throw IOException("Could not read the file.")
        return MultipartBody.Part.createFormData(partName, name, bytes.toRequestBody(mime.toMediaTypeOrNull()))
    }

    fun text(v: String) = v.toRequestBody("text/plain".toMediaTypeOrNull())
}
