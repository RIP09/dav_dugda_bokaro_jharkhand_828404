<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    /** Usage: ->middleware('role:admin,teacher') */
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();
        if (! $user || ! in_array($user->role, $roles, true)) {
            return response()->json(['message' => 'This action is not allowed for your role.'], 403);
        }
        if (! $user->is_active) {
            return response()->json(['message' => 'Your account is disabled. Please contact the school office.'], 403);
        }
        return $next($request);
    }
}
