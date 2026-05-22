<?php

use App\Exceptions\BaseException;
use App\Http\Middleware\RequireRole;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['role' => RequireRole::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'type' => 'unauthorized',
                'title' => 'Unauthorized',
                'status' => 401,
                'instance' => $request->path(),
            ], 401)
                ->header('Content-Type', 'application/problem+json')
                ->header('WWW-Authenticate', 'Bearer');
        });

        $exceptions->render(function (BaseException $e, Request $request) {
            $response = response()->json([
                'type' => $e->getType(),
                'title' => $e->getTitle(),
                'status' => $e->getStatus(),
                'instance' => $request->path(),
            ], $e->getStatus())
                ->header('Content-Type', 'application/problem+json');

            if ($e->getStatus() === 401) {
                $response->header('WWW-Authenticate', 'Bearer');
            }

            return $response;
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            return response()->json([
                'type' => 'validation_error',
                'title' => 'Validation Failed',
                'status' => 422,
                'instance' => $request->path(),
                'errors' => $e->errors(),
            ], 422)
                ->header('Content-Type', 'application/problem+json');
        });
    })->create();
