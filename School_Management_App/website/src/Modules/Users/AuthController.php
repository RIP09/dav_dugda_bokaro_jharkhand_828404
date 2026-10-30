<?php

namespace Website\Modules\Users;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Website\Support\ChangeLog;

class AuthController extends Controller
{
    public function show()
    {
        if (Auth::guard('webadmin')->check()) {
            return redirect()->route('webadmin.dashboard');
        }

        return view('website::cms.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        $key = 'webadmin-login:'.Str::lower($data['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $sec = RateLimiter::availableIn($key);

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => "Too many wrong tries. Please wait {$sec} seconds and try again."]);
        }

        $ok = Auth::guard('webadmin')->attempt(
            ['email' => $data['email'], 'password' => $data['password'], 'is_active' => true],
            $request->boolean('remember')
        );
        if (! $ok) {
            RateLimiter::hit($key, 60);

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Wrong email or password for the website admin panel.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $admin = Auth::guard('webadmin')->user();
        $admin->forceFill(['last_login_at' => now()])->saveQuietly();
        ChangeLog::record('login', 'users', $admin, [], $admin->name);

        return redirect()->intended(route('webadmin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('webadmin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('webadmin.login')->with('ok', 'You are signed out.');
    }
}
