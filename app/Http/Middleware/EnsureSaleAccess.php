<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSaleAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        abort_unless($user && ($user->hasRole('admin') || ($user->hasRole('kasir') && $user->cabang()->exists())), 403);

        return $next($request);
    }
}
