<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsSubscribed {

    public function handle(Request $request, Closure $next) {
        if (!auth()->user()?->subscribed('default')) {
            return response()->json(['message' => 'Subscription required'], 403);
        }

        return $next($request);
    }
}