<?php

use App\Http\Middleware\Admin;
use App\Http\Middleware\Keuangan;
use App\Http\Middleware\Owner;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\Santri;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'guest' => RedirectIfAuthenticated::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'admin' => Admin::class,
            'keuangan' => Keuangan::class,
            'santri' => Santri::class,
            'owner' => Owner::class,
        ]);

        $middleware->group('Administrator', [
            Admin::class,
        ]);

        $middleware->group('Keuangan', [
            Keuangan::class,
        ]);

        $middleware->redirectTo(
            guests: '/login',
            users: '/dashboard',
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*') || $request->is('v1/*') || $request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Too many requests. Rate limit exceeded.',
                    'errors' => [
                        'rate_limit' => ['You have exceeded the allowed limit of 60 requests per minute.'],
                    ],
                ], 429, $e->getHeaders());
            }
        });
    })->create();
