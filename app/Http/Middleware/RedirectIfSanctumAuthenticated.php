<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfSanctumAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->bearerToken() && $request->hasCookie('auth_token')) {
            $request->headers->set('Authorization', 'Bearer ' . $request->cookie('auth_token'));
        }

        if (Auth::guard('sanctum')->check()) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
