package com.davdugda.sms.core.ui

import androidx.compose.runtime.MutableState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.davdugda.sms.core.data.AppContainer
import com.davdugda.sms.core.ui.components.Load
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.launch

/** Shared MVVM plumbing: loading into a state holder, running actions, and one-shot messages. */
abstract class BaseViewModel(protected val c: AppContainer) : ViewModel() {
    val api get() = c.api

    /** One-shot message shown as a snackbar by the screen. */
    var message by mutableStateOf<String?>(null)
    var busy by mutableStateOf(false)
        protected set

    protected fun <T> load(state: MutableState<Load<T>>, silent: Boolean = false, block: suspend () -> T) {
        viewModelScope.launch {
            if (!silent || state.value !is Load.Ok) state.value = Load.Loading
            state.value = try {
                Load.Ok(block())
            } catch (e: CancellationException) {
                throw e
            } catch (e: Throwable) {
                Load.Err(c.errorMessage(e))
            }
        }
    }

    /** Runs a write call; [block] returns the server's message (or null to use [success]). */
    protected fun act(success: String? = null, onDone: () -> Unit = {}, block: suspend () -> String?) {
        if (busy) return
        viewModelScope.launch {
            busy = true
            try {
                message = block() ?: success
                onDone()
            } catch (e: CancellationException) {
                throw e
            } catch (e: Throwable) {
                message = c.errorMessage(e)
            } finally {
                busy = false
            }
        }
    }
}

fun <T> loadState(): MutableState<Load<T>> = mutableStateOf(Load.Loading)
