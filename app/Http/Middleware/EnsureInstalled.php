<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checks if IntraCare has been set up (at least one admin user exists).
 * If not, redirects all routes to the setup wizard.
 */
class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if already on setup route
        if ($request->routeIs('setup.*')) {
            return $next($request);
        }

        // Check if any user exists (cached for performance)
        $installed = cache()->remember('intracare.installed', 3600, function () {
            return User::count() > 0;
        });

        if (! $installed) {
            return redirect()->route('setup.index');
        }

        return $next($request);
    }
}
