<?php

/**
 * Property 7: Distance-Based Attendance Status Determination
 * 
 * Feature: api-absensi-geolocation, Property 7: Distance-Based Attendance Status Determination
 * 
 * For any attendance submission where calculated distance <= location radius, status should be "hadir".
 * For any attendance submission where calculated distance > location radius, status should be "ditolak".
 * 
 * Validates: Requirements 4.6, 4.7
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
 * Helper function to generate random valid coordinates within Indonesia range
 */
function generateIndonesiaCoordinates(): array
{
    return [
        'latitude' => fake()->latitude(-8, -6),
        'longitude' => fake()->longitude(106, 108),
    ];
}

/**
 * Helper function to create test data for attendance
 */
function createTestScheduleWithLocation(array $locationCoords, float $radius): Schedule
{
    $location = Location::create([
        'name' => fake()->company(),
        'latitude' => $locationCoords['latitude'],
        'longitude' => $locationCoords['longitude'],
        'radius' => $radius,
    ]);

    $course = Course::create([
        'course_name' => fake()->sentence(3),
        'course_code' => fake()->unique()->regexify('[A-Z]{2}[0-9]{3}'),
        'lecturer_name' => fake()->name(),
        'location' => fake()->address(),
    ]);

    // Create schedule that is currently active
    $now = Carbon::now();
    return Schedule::create([
        'course_id' => $course->id,
        'location_id' => $location->id,
        'start_time' => $now->copy()->subHour(),
        'end_time' => $now->copy()->addHour(),
    ]);
}

/**
 * Property 7.1: When distance <= radius, status should be "hadir"
 * For any user coordinates within the location radius, attendance status must be "hadir"
 */
test('Property 7.1: Distance within radius results in status hadir', function () {
    for ($i = 0; $i < 100; $i++) {
        // Clean up previous test data
        Attendance::query()->delete();
        Schedule::query()->delete();
        Course::query()->delete();
        Location::query()->delete();
        User::query()->delete();

        // Create user
        $user = User::factory()->create(['role' => 'mahasiswa']);

        // Generate location coordinates
        $locationCoords = generateIndonesiaCoordinates();
        $radius = fake()->randomFloat(2, 50, 500); // 50m to 500m radius

        // Create schedule with location
        $schedule = createTestScheduleWithLocation($locationCoords, $radius);

        // Generate user coordinates that are WITHIN the radius
        // Use same coordinates (distance = 0, which is always <= radius)
        $userLat = $locationCoords['latitude'];
        $userLon = $locationCoords['longitude'];

        $result = $this->attendanceService->processAttendance(
            $user,
            $schedule->id,
            $userLat,
            $userLon
        );

        expect($result['success'])->toBeTrue();
        expect($result['status'])->toBe('hadir');
        expect($result['distance'])->toBeLessThanOrEqual($radius);
    }
})->group('property');

/**
 * Property 7.2: When distance > radius, status should be "ditolak"
 * For any user coordinates outside the location radius, attendance status must be "ditolak"
 */
test('Property 7.2: Distance outside radius results in status ditolak', function () {
    for ($i = 0; $i < 100; $i++) {
        // Clean up previous test data
        Attendance::query()->delete();
        Schedule::query()->delete();
        Course::query()->delete();
        Location::query()->delete();
        User::query()->delete();

        // Create user
        $user = User::factory()->create(['role' => 'mahasiswa']);

        // Generate location coordinates
        $locationCoords = generateIndonesiaCoordinates();
        $radius = fake()->randomFloat(2, 50, 200); // Small radius 50m to 200m

        // Create schedule with location
        $schedule = createTestScheduleWithLocation($locationCoords, $radius);

        // Generate user coordinates that are OUTSIDE the radius
        // Add significant offset to ensure distance > radius (approximately 1km offset)
        $userLat = $locationCoords['latitude'] + 0.01; // ~1.1km offset
        $userLon = $locationCoords['longitude'] + 0.01;

        $result = $this->attendanceService->processAttendance(
            $user,
            $schedule->id,
            $userLat,
            $userLon
        );

        expect($result['success'])->toBeTrue();
        expect($result['status'])->toBe('ditolak');
        expect($result['distance'])->toBeGreaterThan($radius);
    }
})->group('property');

/**
 * Property 7.3: Status determination is consistent with distance calculation
 * For any attendance, the status should be exactly determined by comparing distance with radius
 */
test('Property 7.3: Status is consistent with distance-radius comparison', function () {
    for ($i = 0; $i < 100; $i++) {
        // Clean up previous test data
        Attendance::query()->delete();
        Schedule::query()->delete();
        Course::query()->delete();
        Location::query()->delete();
        User::query()->delete();

        // Create user
        $user = User::factory()->create(['role' => 'mahasiswa']);

        // Generate location coordinates
        $locationCoords = generateIndonesiaCoordinates();
        $radius = fake()->randomFloat(2, 100, 1000);

        // Create schedule with location
        $schedule = createTestScheduleWithLocation($locationCoords, $radius);

        // Generate random user coordinates
        $userCoords = generateIndonesiaCoordinates();

        $result = $this->attendanceService->processAttendance(
            $user,
            $schedule->id,
            $userCoords['latitude'],
            $userCoords['longitude']
        );

        // Calculate expected distance
        $expectedDistance = $this->geolocationService->calculateDistance(
            $userCoords['latitude'],
            $userCoords['longitude'],
            $locationCoords['latitude'],
            $locationCoords['longitude']
        );

        // Verify status matches distance comparison
        $expectedStatus = $expectedDistance <= $radius ? 'hadir' : 'ditolak';
        
        expect($result['success'])->toBeTrue();
        expect($result['status'])->toBe($expectedStatus);
    }
})->group('property');
