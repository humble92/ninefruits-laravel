<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Request id, IP address and user agent for logs, queued jobs and audit records
 * (Laravel Context is propagated to queued jobs automatically).
 */
class AssignRequestContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->headers->get('X-Request-Id');

        if (! is_string($requestId) || ! preg_match('/^[A-Za-z0-9._-]{8,64}$/', $requestId)) {
            $requestId = (string) Str::uuid();
        }

        Context::add([
            'request_id' => $requestId,
            'ip_address' => $request->ip(),
        ]);
        Context::addHidden('user_agent', mb_substr((string) $request->userAgent(), 0, 500));

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
