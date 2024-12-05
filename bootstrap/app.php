<?php

use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use App\Http\Middleware\AuthenticateApi;
use App\Http\Middleware\EnsureJsonAuthenticated;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        
        $middleware->alias([
            'auth' => Authenticate::class,
            'role' => CheckRole::class,
            'admin' => AdminAuth::class,
            
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        // $exceptions->render(function (AuthenticationException $e, Request $request) {
        //     if ($request->is('api/*')) {
        //         return response()->json([
        //             'message' => $e->getMessage(),
        //         ], 401);
        //     }
        // });
    })->create();
