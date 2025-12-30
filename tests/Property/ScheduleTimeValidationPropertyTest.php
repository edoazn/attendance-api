<?php

/**
 * Property 9: Schedule Time Validation
 * 
 * Feature: api-absensi-geolocation, Property 9: Schedule Time Validation
 * 
 * For any attendance submission, if current time is outside the schedule's start_time to end_time range,
 * the attendance should be rejected with appropriate message.
 * 
 * Validates: Requirements 4.2, 4.3
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
function cleanupScheduleTimeTestData(): void
{
    Attendance::query()->delete();
    Schedule::query()->delete();
    Course::query()->delete();
    Location::query()->delete();
    User::query()->delete();
}

/**
 * Helper function to create a schedule with specific time range
 */
function createScheduleWithTimeRange(Carbon $startTime, Carbon $endTime): Schedule
{
    $location = Location::create([
        'name' => fake()->company(),
        'latitude' => fake()->latitude(-8, -6),
        'longitude' => fake()->longitude(106, 108),
        'radius' => 500, // Large radius to ensure location is not the issue
    ]);

    $course = Course::create([
        'course_name' => fake()->sentence(3),
        'course_code' => fake()->unique()->regexify('[A-Z]{2}[0-9]{3}'),
        'lecturer_name' => fake()->name(),
        'location' => fake()->address(),
    ]);

    return Schedule::create([
        'course_id' => $course->id,
        'location_id' => $location->id,
        'start_time' => $startTime,
        'end_time' => $endTime,
    ]);
}

/**
 * Property 9.1: Attendance within schedule time range should be allowed
 * For any attendance submission where current time is within start_time and end_time, processing should proceed
 */
test('Property 9.1: Attendance within schedule time range is allowed', function () {
    for ($i = 0; $i < 100; $i++) {
        cleanupScheduleTimeTestData();

        $user = User::factory()->create(['role' => 'mahasiswa']);

        // Create schedule that is currently active (now is between start and end)
        $now = Carbon::now();
        $startTime = $now->copy()->subMinutes(fake()->numberBetween(10, 60));
        $endTime = $now->copy()->addMinutes(fake()->numberBetween(10, 60));

        $schedule = createScheduleWithTimeRange($startTime, $endTime);

        // Use coordinates at the location (within radius)
        $userLat = $schedule->location->latitude;
        $userLon = $schedule->location->longitude;

        $result = $this->attendanceService->processAttendance(
            $user,
            $schedule->id,
            $userLat,
            $userLon
        );

        // Should succeed (not rejected due to time)
        expect($result['success'])->toBeTrue();
        expect($result['message'])->not->toBe('Absensi hanya dapat dilakukan pada waktu jadwal aktif');
    }
})->group('property');

/**
 * Property 9.2: Attendance before schedule start time should be rejected
 * For any attendance submission where current time is before start_time, it should be rejected
 */
test('Property 9.2: Attendance before schedule start time is rejected', function () {
    for ($i = 0; $i < 100; $i++) {
        cleanupScheduleTimeTestData();

        $user = User::factory()->create(['role' => 'mahasiswa']);

        // Create schedule that starts in the future
        $now = Carbon::now();
        $startTime = $now->copy()->addMinutes(fake()->numberBetween(30, 120));
        $endTime = $startTime->copy()->addHours(2);

        $schedule = createScheduleWithTimeRange($startTime, $endTime);

        // Use coordinates at the location
        $userLat = $schedule->location->latitude;
        $userLon = $schedule->location->longitude;

        $result = $this->attendanceService->processAttendance(
            $user,
            $schedule->id,
            $userLat,
            $userLon
        );

        // Should be rejected due to time
        expect($result['success'])->toBeFalse();
        expect($result['message'])->toBe('Absensi hanya dapat dilakukan pada waktu jadwal aktif');
    }
})->group('property');

/**
 * Property 9.3: Attendance after schedule end time should be rejected
 * For any attendance submission where current time is after end_time, it should be rejected
 */
test('Property 9.3: Attendance after schedule end time is rejected', function () {
    for ($i = 0; $i < 100; $i++) {
        cleanupScheduleTimeTestData();

        $user = User::factory()->create(['role' => 'mahasiswa']);

        // Create schedule that has already ended
        $now = Carbon::now();
        $endTime = $now->copy()->subMinutes(fake()->numberBetween(30, 120));
        $startTime = $endTime->copy()->subHours(2);

        $schedule = createScheduleWithTimeRange($startTime, $endTime);

        // Use coordinates at the location
        $userLat = $schedule->location->latitude;
        $userLon = $schedule->location->longitude;

        $result = $this->attendanceService->processAttendance(
            $user,
            $schedule->id,
            $userLat,
            $userLon
        );

        // Should be rejected due to time
        expect($result['success'])->toBeFalse();
        expect($result['message'])->toBe('Absensi hanya dapat dilakukan pada waktu jadwal aktif');
    }
})->group('property');
