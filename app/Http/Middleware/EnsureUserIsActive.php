<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reject authenticated users whose account has been deactivated.
 *
 * This prevents a disabled employee from continuing to use an
 * existing session after an administrator toggles `is_active`.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('filament.admin.auth.login')
                ->with('notification', [
                    'title' => 'Account deactivated',
                    'body' => 'Your account has been deactivated. Please contact an administrator.',
                    'status' => 'danger',
                ]);
        }

        return $next($request);
    }
}
