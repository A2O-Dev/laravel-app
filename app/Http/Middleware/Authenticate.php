<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Auth;

class Authenticate extends Middleware {
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param \Illuminate\Http\Request $request
     * @return string|null
     */
    protected function redirectTo($request) {
        if (!$request->expectsJson()) {
            return route('login');
        }
    }

    public function handle($request, $next, ...$guards) {
        try {
            $this->authenticate($request, $guards);
        } catch (AuthenticationException $e) {
            $apiRes = new ApiResponse('Auth');
            $apiRes->results = Auth::check();
            $apiRes->errors->add('unauthenticated', 'You need authentication');
            return response()->json($apiRes);
        }
        return $next($request);
    }

    protected function unauthenticated($request, array $guards) {
        throw new AuthenticationException(
            'Unauthenticated.', $guards
        );
    }
}
