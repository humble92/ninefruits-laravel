<?php

use App\Http\Middleware\AssignRequestContext;
use App\Http\Middleware\EnforceMfaPolicy;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SetDatabaseSessionContext;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(AssignRequestContext::class);

        // The admin panel lists the same middleware in AdminPanelProvider.
        $middleware->web(append: [
            EnsureUserIsActive::class,
            SetDatabaseSessionContext::class,
            SetLocale::class,
        ]);

        // Apply account requirements and the user's locale and database context before resolving records.
        $middleware->prependToPriorityList(SubstituteBindings::class, EnsureEmailIsVerified::class);
        $middleware->prependToPriorityList(EnsureEmailIsVerified::class, EnforceMfaPolicy::class);
        $middleware->prependToPriorityList(EnforceMfaPolicy::class, SetLocale::class);
        $middleware->prependToPriorityList(SetLocale::class, SetDatabaseSessionContext::class);
        $middleware->prependToPriorityList(SetDatabaseSessionContext::class, EnsureUserIsActive::class);

        $middleware->alias([
            'mfa' => EnforceMfaPolicy::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('portal.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // The same reference the browser error page shows, so a report can be matched to the logs.
        $exceptions->respond(function (Response $response): Response {
            $requestId = Context::get('request_id');

            if ($response instanceof JsonResponse && is_string($requestId) && is_array($data = $response->getData(true))) {
                $response->setData([...$data, 'request_id' => $requestId]);
            }

            return $response;
        });
    })->create();
