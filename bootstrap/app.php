<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);

        $middleware->redirectGuestsTo(fn ($request) => $request->is('api/*') ? null : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle API exceptions with standardized error responses
        $exceptions->render(function (\Throwable $e, $request) {
            // Only format as JSON for API requests
            if (!$request->is('api/*')) {
                return null; // Let Laravel handle non-API exceptions normally
            }

            $statusCode = 500;
            $message = 'Server error';
            $errors = [];

            // Map specific exception types to appropriate status codes and messages
            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                $statusCode = 404;
                $message = 'Resource not found';
            } elseif ($e instanceof AuthenticationException) {
                $statusCode = 401;
                $message = 'Unauthenticated';
            } elseif ($e instanceof AuthorizationException) {
                $statusCode = 403;
                $message = 'Unauthorized';
            } elseif ($e instanceof ValidationException) {
                $statusCode = 422;
                $message = $e->getMessage();
                $errors = $e->errors();
            } elseif ($e instanceof ThrottleRequestsException) {
                $statusCode = 429;
                $message = 'Too many requests';
            } elseif (method_exists($e, 'getStatusCode')) {
                $statusCode = $e->getStatusCode();
                $message = $e->getMessage() ?: 'Server error';
            } elseif ($e->getMessage()) {
                $message = config('app.debug') ? $e->getMessage() : 'Server error';
            }

            $response = [
                'success' => false,
                'message' => $message,
            ];

            if (!empty($errors)) {
                $response['errors'] = $errors;
            }

            if (config('app.debug')) {
                $response['debug'] = [
                    'exception' => get_class($e),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                    'trace'     => $e->getTraceAsString(),
                ];
            }

            return response()->json($response, $statusCode);
        });
    })->create();
