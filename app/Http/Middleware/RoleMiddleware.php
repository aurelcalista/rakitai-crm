<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Checks that the authenticated user's role matches one of the allowed roles.
     * Usage in routes: ->middleware('role:Sales') or ->middleware('role:Sales,CS')
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $userRole = strtolower(auth()->user()->role);

        // Normalize roles for comparison (case-insensitive)
        $allowedRoles = array_map('strtolower', $roles);

        if (!in_array($userRole, $allowedRoles)) {
            if ($request->expectsJson() || !$request->isMethod('GET')) {
                abort(403, 'Anda tidak memiliki akses ke aksi tersebut.');
            }

            // Redirect to their own dashboard instead of raw 403
            $redirectRoute = 'dashboard.' . $userRole;
            if (app('router')->has($redirectRoute)) {
                return redirect()->route($redirectRoute)
                    ->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
            }
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
