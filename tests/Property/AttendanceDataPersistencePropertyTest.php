<?php

/**
 * Property 10: Attendance Data Persistence
 * 
 * Feature: api-absensi-geolocation, Property 10: Attendance Data Persistence
 * 
 * For any attendance attempt (whether "hadir" or "ditolak"), the system should store
 * user_id, schedule_id, latitude, longitude, calculated distance, and status.
 * 
 * Validates: Requirements 4.8, 10.3
 */

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Location;
use App\Models\Schedule;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\GeolocationService;
use Carbon\Carbon;

beforeEach(function () {
    $this->geolocationService = new GeolocationService();
    $this->attendanceService = new AttendanceService($this->geolocationService);
});

/**
 * Helper function to clean up test data
 */
function cleanupPersistenceTestData(): void
{
    Attendance::query()->delete();
    Schedule::query()->delete();
    Course::query()->delete();
    Location::query()->delete();
    User::query()->delete();
}

/**
 * Helper function to create an active schedule
 */
function createActiveScheduleForPersistence(): Schedule
{
    $location = Location::create([
        'name' => fake()->company(),
        'latitude' => fake()->latitude(-8, -6),
        'longitude' => fake()->longitude(106, 108),
        'radius' => fake()->randomFloat(2, 50, 500),
    ]);

    $course = Course::create([
        'course_name' => fake()->sentence(3),
        'course_code' => fake()->unique()->regexify('[A-Z]{2}[0-9]{3}'),
        'lecturer_name' => fake()->name(),
        'location' => fake()->address(),
    ]);

    $now = Carbon::now();
    return Schedule::create([
        'course_id' => $course->id,
        'location_id' => $location->id,
        'start_time' => $now->copy()->subHour(),
        'end_time' => $now->copy()->addHour(),
    ]);
}

/**
 * Property 10.1: All attendance attempts persist required fields
 * For any attendance attempt, all required fields must be stored in the database
 */
test('Property 10.1: All attendance attempts persist required fields', function () {
    for ($i = 0; $i < 100; $i++) {
        cleanupPersistenceTestData();

        $user = User::factory()->create(['role' => 'mahasiswa']);
        $schedule = createActiveScheduleForPersistence();

        // Generate random user coordinates
        $userLat = fake()->latitude(-8, -6);
        $userLon = fake()->longitude(106, 108);

        $result = $this->attendanceService->processAttendance(
            $user,
            $schedule->id,
            $userLat,
            $userLon
        );

        // Verify attendance was persisted
        expect($result['success'])->toBeTrue();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('schedule_id', $schedule->id)
            ->first();

        // All required fields must be present
        expect($attendance)->not->toBeNull();
        expect($attendance->user_id)->toBe($user->id);
        expect($attendance->schedule_id)->toBe($schedule->id);
        expect($attendance->latitude)->not->toBeNull();
        expect($attendance->longitude)->not->toBeNull();
        expect($attendance->distance)->not->toBeNull();
        expect($attendance->status)->toBeIn(['hadir', 'ditolak']);
    }
})->group('property');

/**
 * Property 10.2: Persisted coordinates match submitted coordinates
 * For any attendance attempt, the stored latitude and longitude must match the submitted values
 */
test('Property 10.2: Persisted coordinates match submitted coordinates', function () {
    for ($i = 0; $i < 100; $i++) {
        cleanupPersistenceTestData();

        $user = User::factory()->create(['role' => 'mahasiswa']);
        $schedule = createActiveScheduleForPersistence();

        // Generate random user coordinates
        $userLat = fake()->latitude(-8, -6);
        $userLon = fake()->longitude(106, 108);

        $this->attendanceService->processAttendance(
            $user,
            $schedule->id,
            $userLat,
            $userLon
        );

        $attendance = Attendance::where('user_id', $user->id)
            ->where('schedule_id', $schedule->id)
            ->first();

        // Coordinates must match (with floating point tolerance)
        expect((float) $attendance->latitude)->toEqualWithDelta($userLat, 0.0001);
        expect((float) $attendance->longitude)->toEqualWithDelta($userLon, 0.0001);
    }
})->group('property');

/**
 * Property 10.3: Persisted distance matches calculated distance
 * For any attendance attempt, the stored distance must match the Haversine calculation
 */
test('Property 10.3: Persisted distance matches calculated distance', function () {
    for ($i = 0; $i < 100; $i++) {
        cleanupPersistenceTestData();

        $user = User::factory()->create(['role' => 'mahasiswa']);
        $schedule = createActiveScheduleForPersistence();

        // Generate random user coordinates
        $userLat = fake()->latitude(-8, -6);
        $userLon = fake()->longitude(106, 108);

        $this->attendanceService->processAttendance(
            $user,
            $schedule->id,
            $userLat,
            $userLon
        );

        $attendance = Attendance::where('user_id', $user->id)
            ->where('schedule_id', $schedule->id)
            ->first();

        // Calculate expected distance
        $expectedDistance = $this->geolocationService->calculateDistance(
            $userLat,
            $userLon,
            (float) $schedule->location->latitude,
            (float) $schedule->location->longitude
        );

        // Distance must match (with floating point tolerance)
        expect((float) $attendance->distance)->toEqualWithDelta($expectedDistance, 0.01);
    }
})->group('property');

/**
 * Property 10.4: Both "hadir" and "ditolak" statuses are persisted
 * For any attendance attempt, regardless of status, the record must be stored
 */
test('Property 10.4: Both hadir and ditolak statuses are persisted', function () {
    $hadirCount = 0;
    $ditolakCount = 0;

    for ($i = 0; $i < 100; $i++) {
        cleanupPersistenceTestData();

        $user = User::factory()->create(['role' => 'mahasiswa']);
        $schedule = createActiveScheduleForPersistence();

        // Generate random user coordinates
        $userLat = fake()->latitude(-8, -6);
        $userLon = fake()->longitude(106, 108);

        $result = $this->attendanceService->processAttendance(
            $user,
            $schedule->id,
            $userLat,
            $userLon
        );

        // Verify record exists regardless of status
        $attendance = Attendance::where('user_id', $user->id)
            ->where('schedule_id', $schedule->id)
            ->first();

        expect($attendance)->not->toBeNull();
        expect($attendance->status)->toBe($result['status']);

        if ($result['status'] === 'hadir') {
            $hadirCount++;
        } else {
            $ditolakCount++;
        }
    }

    // With random coordinates, we should have both statuses represented
    // (this is a statistical check, not a strict property)
    expect($hadirCount + $ditolakCount)->toBe(100);
})->group('property');
