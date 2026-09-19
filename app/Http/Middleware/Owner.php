<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Owner
{
    /**
     * Handle an incoming request.
     *
     * Note: Architecture audit (Phase 5.8.7A/B) confirmed this middleware is currently
     * not attached to any routes, and no "Owner" role or permission exists in the system.
     * Per project architectural constraints, authorization logic is not guessed and this
     * remains a pass-through stub until domain requirements for ownership are specified.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
