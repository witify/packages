<?php

namespace Witify\Devops\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPortalHealthToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = config('devops.token');

        abort_if(! is_string($configuredToken) || $configuredToken === '', Response::HTTP_NOT_FOUND);

        $providedToken = $request->bearerToken();

        abort_unless(
            is_string($providedToken) && hash_equals($configuredToken, $providedToken),
            Response::HTTP_FORBIDDEN,
        );

        return $next($request);
    }
}
