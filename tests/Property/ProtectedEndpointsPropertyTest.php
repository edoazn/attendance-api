<?php

/**
 * Property 2: Protected Endpoints Require Authentication
 * 
 * Feature: api-absensi-geolocation, Property 2: Protected Endpoints Require Authentication
 * 
 * For any attendance-related endpoint (POST /api/v1/attendance, GET /api/v1/attendance/history, 
 * GET /api/v1/schedules/today), requests without valid authentication token should return 401 Unauthorized.
 * 
 * Validates: Requirements 1.5
 */

use App\Models\User;
use App\Models\Location;
use App\Models\Course;
use App\Models\Schedule;

/**
 * Property 2.1: POST /api/v1/attendance requires authentication
 * For any request to POST /api/v1/attendance without a valid token, the system should return 401
 */
test('Property 2.1: POST /api/attendance requires authentication', function () {
    // Create test data for valid attendance request
    $location = Location::create([
        'name' => 'Test Location',
        'latitude' => -6.2088,
        'longitude' => 106.8456,
        'radius' => 100,
    ]);

    $course = Course::create([
        'course_name' => 'Test Course',
        'course_code' => 'TC001',
        'lecturer_name' => 'Test Lecturer',
        'location' => 'Room 101',
    ]);

    $schedule = Schedule::create([
        'course_id' => $course->id,
        'location_id' => $location->id,
        'start_time' => now()->subHour(),
        'end_time' => now()->addHour(),
    ]);

    for ($i = 0; $i < 100; $i++) {
        // Generate random valid attendance data
        $attendanceData = [
            'schedule_id' => $schedule->id,
            'latitude' => fake()->latitude(-90, 90),
            'longitude' => fake()->longitude(-180, 180),
        ];

        // Request without authentication token
        $response = $this->postJson('/api/v1/attendance', $attendanceData);

        $response->assertStatus(401);
    }

    // Cleanup
    $schedule->forceDelete();
    $course->forceDelete();
    $location->forceDelete();
})->group('property');

/**
 * Property 2.2: GET /api/v1/attendance/history requires authentication
 * For any request to GET /api/v1/attendance/history without a valid token, the system should return 401
 */
test('Property 2.2: GET /api/attendance/history requires authentication', function () {
    for ($i = 0; $i < 100; $i++) {
        // Request without authentication token
        $response = $this->getJson('/api/v1/attendance/history');

        $response->assertStatus(401);
    }
})->group('property');

/**
 * Property 2.3: GET /api/v1/schedules/today requires authentication
 * For any request to GET /api/v1/schedules/today without a valid token, the system should return 401
 */
test('Property 2.3: GET /api/schedules/today requires authentication', function () {
    for ($i = 0; $i < 100; $i++) {
        // Request without authentication token
        $response = $this->getJson('/api/v1/schedules/today');

        $response->assertStatus(401);
    }
})->group('property');

/**
 * Property 2.4: Authenticated requests to protected endpoints succeed
 * For any valid authenticated user, requests to protected endpoints should not return 401
 */
test('Property 2.4: Authenticated requests to protected endpoints succeed', function () {
    // Create test data
    $location = Location::create([
        'name' => 'Test Location',
        'latitude' => -6.2088,
        'longitude' => 106.8456,
        'radius' => 100,
    ]);

    $course = Course::create([
        'course_name' => 'Test Course',
        'course_code' => 'TC002',
        'lecturer_name' => 'Test Lecturer',
        'location' => 'Room 101',
    ]);

    $schedule = Schedule::create([
        'course_id' => $course->id,
        'location_id' => $location->id,
        'start_time' => now()->subHour(),
        'end_time' => now()->addHour(),
    ]);

    for ($i = 0; $i < 100; $i++) {
        // Create a random user
        $user = User::create([
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password123'),
            'role' => 'mahasiswa',
        ]);

        // Authenticate the user
        $this->actingAs($user, 'sanctum');

        // Test GET /api/v1/attendance/history - should not return 401
        $historyResponse = $this->getJson('/api/v1/attendance/history');
        expect($historyResponse->status())->not->toBe(401);

        // Test GET /api/v1/schedules/today - should not return 401
        $todayResponse = $this->getJson('/api/v1/schedules/today');
        expect($todayResponse->status())->not->toBe(401);

        // Cleanup
        $user->tokens()->delete();
        $user->forceDelete();
    }

    // Cleanup test data
    $schedule->forceDelete();
    $course->forceDelete();
    $location->forceDelete();
})->group('property');

/**
 * Property 2.5: Invalid/expired tokens return 401
 * For any request with an invalid token, the system should return 401
 */
test('Property 2.5: Invalid tokens return 401', function () {
    for ($i = 0; $i < 100; $i++) {
        // Generate random invalid token
        $invalidToken = fake()->sha256();

        // Test with invalid token on all protected endpoints
        $historyResponse = $this->withHeader('Authorization', 'Bearer ' . $invalidToken)
            ->getJson('/api/v1/attendance/history');
        $historyResponse->assertStatus(401);

        $todayResponse = $this->withHeader('Authorization', 'Bearer ' . $invalidToken)
            ->getJson('/api/v1/schedules/today');
        $todayResponse->assertStatus(401);

        $attendanceResponse = $this->withHeader('Authorization', 'Bearer ' . $invalidToken)
            ->postJson('/api/v1/attendance', [
                'schedule_id' => 1,
                'latitude' => fake()->latitude(-90, 90),
                'longitude' => fake()->longitude(-180, 180),
            ]);
        $attendanceResponse->assertStatus(401);
    }
})->group('property');
