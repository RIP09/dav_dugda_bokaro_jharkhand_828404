<?php

namespace Website\Http;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Only signed-in website admins (the `webadmin` guard) may open /website-admin. */
class Authenticate
{
    public function handle(Request $request, Closure $next)
    {
        $guard = Auth::guard('webadmin');
        if (! $guard->check() || ! $guard->user()->is_active) {
            if ($guard->check()) {
                $guard->logout();
            }

            return redirect()->guest(route('webadmin.login'));
        }
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
