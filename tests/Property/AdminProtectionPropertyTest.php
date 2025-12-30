<?php

/**
 * Property 3: Admin-Only Endpoints Protection
 * 
 * Feature: api-absensi-geolocation, Property 3: Admin-Only Endpoints Protection
 * 
 * For any user with role "mahasiswa", requests to admin endpoints (locations, schedules 
 * management, reports) should return 403 Forbidden.
 * 
 * Validates: Requirements 2.4, 3.3, 7.4
 */

use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Register test routes with admin middleware for testing
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('/api/test-admin-endpoint', fn() => response()->json(['message' => 'Admin access granted']));
        Route::post('/api/test-admin-endpoint', fn() => response()->json(['message' => 'Admin access granted']));
        Route::put('/api/test-admin-endpoint/{id}', fn() => response()->json(['message' => 'Admin access granted']));
        Route::delete('/api/test-admin-endpoint/{id}', fn() => response()->json(['message' => 'Admin access granted']));
    });
});

/**
 * Helper function to generate random user data with specific role
 */
function generateUserWithRole(string $role): array
{
    return [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'role' => $role,
    ];
}

/**
 * Property 3.1: Mahasiswa users receive 403 on admin endpoints
 * For any user with role "mahasiswa", accessing admin endpoints should return 403
 */
test('Property 3.1: Mahasiswa users receive 403 on admin endpoints', function () {
    for ($i = 0; $i < 100; $i++) {
        $userData = generateUserWithRole('mahasiswa');
        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => bcrypt($userData['password']),
            'role' => $userData['role'],
        ]);

        $this->actingAs($user, 'sanctum');

        // Test GET request
        $response = $this->getJson('/api/test-admin-endpoint');
        $response->assertStatus(403)
            ->assertJson(['message' => 'Forbidden']);

        // Test POST request
        $response = $this->postJson('/api/test-admin-endpoint');
        $response->assertStatus(403)
            ->assertJson(['message' => 'Forbidden']);

        // Test PUT request
        $response = $this->putJson('/api/test-admin-endpoint/1');
        $response->assertStatus(403)
            ->assertJson(['message' => 'Forbidden']);

        // Test DELETE request
        $response = $this->deleteJson('/api/test-admin-endpoint/1');
        $response->assertStatus(403)
            ->assertJson(['message' => 'Forbidden']);

        $user->tokens()->delete();
        $user->delete();
    }
})->group('property');

/**
 * Property 3.2: Admin users can access admin endpoints
 * For any user with role "admin", accessing admin endpoints should return 200
 */
test('Property 3.2: Admin users can access admin endpoints', function () {
    for ($i = 0; $i < 100; $i++) {
        $userData = generateUserWithRole('admin');
        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => bcrypt($userData['password']),
            'role' => $userData['role'],
        ]);

        $this->actingAs($user, 'sanctum');

        // Test GET request
        $response = $this->getJson('/api/test-admin-endpoint');
        $response->assertStatus(200)
            ->assertJson(['message' => 'Admin access granted']);

        // Test POST request
        $response = $this->postJson('/api/test-admin-endpoint');
        $response->assertStatus(200)
            ->assertJson(['message' => 'Admin access granted']);

        $user->tokens()->delete();
        $user->delete();
    }
})->group('property');

/**
 * Property 3.3: Unauthenticated users receive 401 on admin endpoints
 * For any request without authentication, admin endpoints should return 401
 */
test('Property 3.3: Unauthenticated users receive 401 on admin endpoints', function () {
    for ($i = 0; $i < 100; $i++) {
        // Test GET request without authentication
        $response = $this->getJson('/api/test-admin-endpoint');
        $response->assertStatus(401);

        // Test POST request without authentication
        $response = $this->postJson('/api/test-admin-endpoint');
        $response->assertStatus(401);
    }
})->group('property');
