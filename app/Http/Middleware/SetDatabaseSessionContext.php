<?php

namespace App\Http\Middleware;

use App\Support\Database\DatabaseSessionContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

/**
 * Passes the authenticated user to PostgreSQL (row level security, audit triggers) and
 * clears it when the request ends, because Octane workers reuse database connections.
 */
class SetDatabaseSessionContext
{
    public function __construct(private readonly DatabaseSessionContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->user()?->getAuthIdentifier();

        if ($userId !== null) {
            Context::add('user_id', (int) $userId);
        }

        $this->context->forUser($userId === null ? null : (int) $userId, Context::get('request_id'));

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $this->context->reset();
    }
}
