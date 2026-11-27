<?php

use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $isApi = fn (Request $request) => $request->is('api/*') || $request->expectsJson();

        $exceptions->shouldRenderJsonWhen($isApi);

        $exceptions->render(fn (ValidationException $e, Request $request) => $isApi($request)
            ? ApiResponse::error('Validation failed', 422, $e->errors()) : null);

        $exceptions->render(fn (AuthenticationException $e, Request $request) => $isApi($request)
            ? ApiResponse::error('Unauthenticated', 401) : null);

        $exceptions->render(fn (AuthorizationException $e, Request $request) => $isApi($request)
            ? ApiResponse::error('This action is unauthorized', 403) : null);

        $exceptions->render(fn (ModelNotFoundException $e, Request $request) => $isApi($request)
            ? ApiResponse::error('Resource not found', 404) : null);

        $exceptions->render(fn (NotFoundHttpException $e, Request $request) => $isApi($request)
            ? ApiResponse::error('Resource not found', 404) : null);

        $exceptions->render(fn (ThrottleRequestsException $e, Request $request) => $isApi($request)
            ? ApiResponse::error('Too many requests', 429) : null);

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }
            $status = $e->getStatusCode();

            return ApiResponse::error($status >= 500 ? 'Server error' : ($e->getMessage() ?: 'Request failed'), $status);
        });

        // Never leak internals (SQL errors, stack traces) from API routes.
        $exceptions->render(function (Throwable $e, Request $request) use ($isApi) {
            if (! $isApi($request) || config('app.debug')) {
                return null;
            }

            return ApiResponse::error('Server error', 500);
        });
    })->create();
