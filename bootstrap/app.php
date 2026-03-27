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

/**
 * Handle API exceptions and format them into standardized error responses
 *
 * @param \Throwable $exception
 * @param \Illuminate\Http\Request $request
 * @return \Illuminate\Http\JsonResponse
 */
function handleApiException(\Throwable $exception, $request): \Illuminate\Http\JsonResponse
{
    $statusCode = 500;
    $message = 'Server error';
    $errors = [];

    // Map specific exception types to appropriate status codes and messages
    if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
        $statusCode = 404;
        $message = 'Resource not found';
    } elseif ($exception instanceof AuthenticationException) {
        $statusCode = 401;
        $message = 'Unauthenticated';
    } elseif ($exception instanceof AuthorizationException) {
        $statusCode = 403;
        $message = 'Unauthorized';
    } elseif ($exception instanceof ValidationException) {
        $statusCode = 422;
        $message = $exception->getMessage();
        $errors = $exception->errors();
    } elseif ($exception instanceof ThrottleRequestsException) {
        $statusCode = 429;
        $message = 'Too many requests';
    } elseif (method_exists($exception, 'getStatusCode')) {
        // Handle HTTP exceptions
        $statusCode = $exception->getStatusCode();
        $message = $exception->getMessage() ?: 'Server error';
    } elseif ($exception->getMessage()) {
        // Use exception message if available (but sanitize in production)
        $message = config('app.debug') ? $exception->getMessage() : 'Server error';
    }

    // Build the standardized error response
    $response = [
        'success' => false,
        'message' => $message,
    ];

    // Add field-specific errors for validation exceptions
    if (!empty($errors)) {
        $response['errors'] = $errors;
    }

    // Include stack trace only in debug mode
    if (config('app.debug')) {
        $response['debug'] = [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];
    }

    return response()->json($response, $statusCode);
}

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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle API exceptions with standardized error responses
        $exceptions->render(function (\Throwable $e, $request) {
            // Only format as JSON for API requests
            if (!$request->is('api/*')) {
                return null; // Let Laravel handle non-API exceptions normally
            }

            return handleApiException($e, $request);
        });
    })->create();
