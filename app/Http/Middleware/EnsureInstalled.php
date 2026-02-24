<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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

        // Public LAN display routes (no auth) should be accessible even before installation.
        if ($request->routeIs('queue.display')) {
            return $next($request);
        }

        // During first install (before migrations) and during some test setups,
        // the users table may not exist yet. Don't hard-fail requests.
        if (! Schema::hasTable('users')) {
            cache()->forget('intracare.installed');

            return $next($request);
        }

        // Check if any user exists (cached for performance)
        $installed = cache()->get('intracare.installed');

        if ($installed === null) {
            $installed = User::query()->exists();
            cache()->put('intracare.installed', $installed, 3600);
        }

        if (! $installed) {
            return redirect()->route('setup.index');
        }

        return $next($request);
    }
}
