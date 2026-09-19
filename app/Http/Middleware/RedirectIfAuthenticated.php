<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * The default redirect path for authenticated users.
     *
     * Hardcoded to remove the RouteServiceProvider::HOME dependency, which will
     * be deleted during the Laravel 11 bootstrap migration (Phase 5.5.2B).
     */
    private const HOME = '/dashboard';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return redirect(self::HOME);
            }
        }

        return $next($request);
    }
}
