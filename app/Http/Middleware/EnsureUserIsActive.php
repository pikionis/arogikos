<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kicks out accounts that were deactivated mid-session.
 *
 * Runs AFTER `auth`. Logging out (rather than 403ing) matters: a deactivated
 * account should not keep a live session that starts working again the moment
 * someone re-enables it.
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
                ->route('admin.login')
                ->withErrors(['email' => __('auth.inactive')]);
        }

        return $next($request);
    }
}
