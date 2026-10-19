<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next)
    {
        if ($user = $request->user()) {
            // Re-read status so an existing session cannot retain stale account data.
            $user = $user->fresh();
            // Pending deployment must not treat the existing Admin as inactive
            // or rotate its remember token before the approved migration.
            if ($user && ! array_key_exists('is_active', $user->getAttributes())) {
                abort(503, 'Migration fondasi akun belum diterapkan.');
            }
            if (! $user || ! $user->is_active) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors(['email' => 'Akun tidak aktif. Hubungi Admin.']);
            }
        }

        return $next($request);
    }
}
