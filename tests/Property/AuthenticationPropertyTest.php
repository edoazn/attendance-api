<?php

/**
 * Property 1: Authentication Token Round-Trip
 * 
 * Feature: api-absensi-geolocation, Property 1: Authentication Token Round-Trip
 * 
 * For any valid user credentials, logging in should return a token, and using that token 
 * for logout should invalidate it such that subsequent requests with that token return 401.
 * 
 * Validates: Requirements 1.1, 1.3
 */

use App\Models\User;

/**
 * Helper function to generate random user data
 */
function generateRandomUserData(): array
{
    return [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'role' => fake()->randomElement(['admin', 'mahasiswa']),
    ];
}

/**
 * Property 1.1: Valid credentials return a token
 * For any valid user, login with correct credentials should return a token
 */
test('Property 1.1: Valid credentials return a token', function () {
    for ($i = 0; $i < 100; $i++) {
        $userData = generateRandomUserData();
        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => bcrypt($userData['password']),
            'role' => $userData['role'],
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $userData['email'],
            'password' => $userData['password'],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'role']
            ]);

        expect($response->json('token'))->not->toBeEmpty();
        expect($response->json('user.email'))->toBe($userData['email']);
        
        // Clean up tokens for next iteration
        $user->tokens()->delete();
        $user->forceDelete();
    }
})->group('property');

/**
 * Property 1.2: Invalid credentials return 401
 * For any user, login with wrong password should return 401
 */
test('Property 1.2: Invalid credentials return 401', function () {
    for ($i = 0; $i < 100; $i++) {
        $userData = generateRandomUserData();
        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => bcrypt($userData['password']),
            'role' => $userData['role'],
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $userData['email'],
            'password' => 'wrong_password_' . fake()->word(),
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
        
        $user->forceDelete();
    }
})->group('property');

/**
 * Property 1.3: Token round-trip - login then logout invalidates token
 * For any valid user, after logout the token should be invalid
 */
test('Property 1.3: Token round-trip - login then logout invalidates token', function () {
    for ($i = 0; $i < 100; $i++) {
        $userData = generateRandomUserData();
        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => bcrypt($userData['password']),
            'role' => $userData['role'],
        ]);

        // Use actingAs with Sanctum to properly authenticate
        $this->actingAs($user, 'sanctum');
        
        // Create a token for the user
        $user->createToken('auth-token');
        
        expect($user->tokens()->count())->toBe(1);

        // Logout
        $logoutResponse = $this->postJson('/api/v1/logout');

        $logoutResponse->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully']);
        
        // Verify token was deleted
        $user->refresh();
        expect($user->tokens()->count())->toBe(0);
        
        // Clean up for next iteration
        $user->forceDelete();
    }
})->group('property');

/**
 * Property 1.4: Non-existent user returns 401
 * For any email that doesn't exist, login should return 401
 */
test('Property 1.4: Non-existent user returns 401', function () {
    for ($i = 0; $i < 100; $i++) {
        $response = $this->postJson('/api/v1/login', [
            'email' => fake()->unique()->safeEmail(),
            'password' => fake()->password(),
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
    }
})->group('property');
