<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UseSanctumTokenCookie
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->bearerToken()) {
            $token = $_COOKIE['auth_token'] ?? null;
            if ($token) {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }
        }

        return $next($request);
    }
}
