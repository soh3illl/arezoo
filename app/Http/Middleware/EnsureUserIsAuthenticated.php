<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reject guests outright rather than sending them to a login page.
 *
 * The framework's `auth` middleware redirects to a route named `login`, which
 * this application does not have yet.
 */
class EnsureUserIsAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user() !== null, Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
