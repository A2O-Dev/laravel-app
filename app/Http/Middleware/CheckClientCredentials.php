<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passport\Exceptions\MissingScopeException;
use Laravel\Passport\Http\Middleware\CheckClientCredentials as Middleware;
use Illuminate\Support\Facades\Auth;
use League\OAuth2\Server\Exception\OAuthServerException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

class CheckClientCredentials extends Middleware {

    /**
     * @param Request $request
     * @param Closure $next
     * @param mixed ...$scopes
     * @return JsonResponse|mixed
     * @throws AuthenticationException
     * @throws MissingScopeException
     */
    public function handle($request, Closure $next, ...$scopes) {
        $psr = (new PsrHttpFactory(
            new Psr17Factory,
            new Psr17Factory,
            new Psr17Factory,
            new Psr17Factory
        ))->createRequest($request);

        try {
            $psr = $this->server->validateAuthenticatedRequest($psr);
        } catch (OAuthServerException $e) {
            $apiRes = new ApiResponse('Auth');
            $apiRes->results = Auth::check();
            $apiRes->errors->add('unauthenticated', 'You need authentication');
            return response()->json($apiRes, 401);
        }

        $this->validate($psr, $scopes);

        return $next($request);
    }
}
