<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

// Define test routes before each test
beforeEach(function () {
    Route::get('api/test/404', function () {
        throw new ModelNotFoundException();
    });

    Route::get('api/test/401', function () {
        throw new AuthenticationException();
    });

    Route::get('api/test/403', function () {
        throw new AuthorizationException();
    });

    Route::get('api/test/422', function () {
        throw ValidationException::withMessages([
            'email' => ['The email field is required.'],
            'password' => ['The password must be at least 8 characters.'],
        ]);
    });

    Route::get('api/test/429', function () {
        throw new ThrottleRequestsException();
    });

    Route::get('api/test/500', function () {
        throw new \Exception('Test server error');
    });

    Route::get('web/test/404', function () {
        throw new ModelNotFoundException();
    });
});

test('ModelNotFoundException returns 404 with standardized error response', function () {
    $response = $this->getJson('api/test/404');

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Resource not found',
        ])
        ->assertJsonMissing(['data']);
});

test('AuthenticationException returns 401 with standardized error response', function () {
    $response = $this->getJson('api/test/401');

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'Unauthenticated',
        ]);
});

test('AuthorizationException returns 403 with standardized error response', function () {
    $response = $this->getJson('api/test/403');

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'Unauthorized',
        ]);
});

test('ValidationException returns 422 with field-specific errors', function () {
    $response = $this->getJson('api/test/422');

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'errors' => [
                'email' => ['The email field is required.'],
                'password' => ['The password must be at least 8 characters.'],
            ],
        ]);
});

test('ThrottleRequestsException returns 429 with standardized error response', function () {
    $response = $this->getJson('api/test/429');

    $response->assertStatus(429)
        ->assertJson([
            'success' => false,
            'message' => 'Too many requests',
        ]);
});

test('generic exceptions return 500 with standardized error response', function () {
    $response = $this->getJson('api/test/500');

    $response->assertStatus(500)
        ->assertJson([
            'success' => false,
        ]);
});

test('debug mode includes stack trace in error response', function () {
    config(['app.debug' => true]);

    $response = $this->getJson('api/test/500');

    $response->assertStatus(500)
        ->assertJsonStructure([
            'success',
            'message',
            'debug' => [
                'exception',
                'file',
                'line',
                'trace',
            ],
        ]);
});

test('production mode excludes stack trace from error response', function () {
    config(['app.debug' => false]);

    $response = $this->getJson('api/test/500');

    $response->assertStatus(500)
        ->assertJson([
            'success' => false,
            'message' => 'Server error',
        ])
        ->assertJsonMissing(['debug']);
});
