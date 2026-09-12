<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // The React Native client in celebrateMobile/ talks to these. Stateless,
        // Sanctum bearer tokens — no session, no CSRF.
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin'          => \App\Http\Middleware\IsAdmin::class,
            'admin.can'      => \App\Http\Middleware\AdminCan::class,
            'admin.super'    => \App\Http\Middleware\AdminIsSuper::class,
            'not.impersonating' => \App\Http\Middleware\NotWhileImpersonating::class,
        ]);

        // Every /api route resolves $request->user() through the token guard.
        $middleware->api(prepend: [
            \App\Http\Middleware\UseSanctumGuard::class,
        ]);

        // Gateways cannot hold a session token. These two verify the request
        // by signature instead — see WebhookController.
        $middleware->validateCsrfTokens(except: [
            'webhooks/paystack',
            'webhooks/stripe',
        ]);

        // Behind a load balancer or CDN the app sees the proxy, not the
        // visitor: without this every request looks like plain HTTP from one
        // IP, which breaks HTTPS URL generation and rate limiting alike.
        $middleware->trustProxies(at: '*', headers:
            Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_AWS_ELB
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
