<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Lets a browser / WebView / Electron open a PDF or export link: ?token=<bearer> becomes the Authorization header. */
class TokenFromQuery
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->bearerToken() && ($t = $request->query('token'))) {
            $request->headers->set('Authorization', 'Bearer '.$t);
        }
        return $next($request);
    }
}
