<?php

/**
 * Property 8: Duplicate Attendance Prevention
 * 
 * Feature: api-absensi-geolocation, Property 8: Duplicate Attendance Prevention
 * 
 * For any user and schedule combination, if an attendance record already exists,
 * subsequent attendance attempts for the same schedule should be rejected.
 * 
 * Validates: Requirements 4.4
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
function generateIndonesiaCoords(): array
{
    return [
        'latitude' => fake()->latitude(-8, -6),
        'longitude' => fake()->longitude(106, 108),
    ];
}

/**
 * Helper function to create test schedule with location
 */
function createActiveSchedule(array $locationCoords, float $radius): Schedule
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
 * Property 8.1: Second attendance attempt for same schedule is rejected
 * For any user who has already submitted attendance for a schedule,
 * a second attempt should be rejected with appropriate message
 */
test('Property 8.1: Duplicate attendance attempt is rejected', function () {
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
        $locationCoords = generateIndonesiaCoords();
        $radius = fake()->randomFloat(2, 100, 500);

        // Create active schedule
        $schedule = createActiveSchedule($locationCoords, $radius);

        // First attendance attempt (should succeed)
        $firstResult = $this->attendanceService->processAttendance(
            $user,
            $schedule->id,
            $locationCoords['latitude'],
            $locationCoords['longitude']
        );

        expect($firstResult['success'])->toBeTrue();

        // Second attendance attempt (should be rejected)
        $secondResult = $this->attendanceService->processAttendance(
            $user,
            $schedule->id,
            $locationCoords['latitude'],
            $locationCoords['longitude']
        );

        expect($secondResult['success'])->toBeFalse();
        expect($secondResult['message'])->toBe('Anda sudah melakukan absensi untuk jadwal ini');
    }
})->group('property');

/**
 * Property 8.2: Only one attendance record exists per user-schedule combination
 * For any user and schedule, after multiple attendance attempts,
 * only one attendance record should exist in the database
 */
test('Property 8.2: Only one attendance record per user-schedule', function () {
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
        $locationCoords = generateIndonesiaCoords();
        $radius = fake()->randomFloat(2, 100, 500);

        // Create active schedule
        $schedule = createActiveSchedule($locationCoords, $radius);

        // Attempt multiple attendances
        $attemptCount = fake()->numberBetween(2, 5);
        for ($j = 0; $j < $attemptCount; $j++) {
            $this->attendanceService->processAttendance(
                $user,
                $schedule->id,
                $locationCoords['latitude'],
                $locationCoords['longitude']
            );
        }

        // Verify only one record exists
        $recordCount = Attendance::where('user_id', $user->id)
            ->where('schedule_id', $schedule->id)
            ->count();

        expect($recordCount)->toBe(1);
    }
})->group('property');

/**
 * Property 8.3: Different users can attend same schedule
 * For any schedule, different users should be able to submit attendance independently
 */
test('Property 8.3: Different users can attend same schedule', function () {
    for ($i = 0; $i < 100; $i++) {
        // Clean up previous test data
        Attendance::query()->delete();
        Schedule::query()->delete();
        Course::query()->delete();
        Location::query()->delete();
        User::query()->delete();

        // Create multiple users
        $userCount = fake()->numberBetween(2, 5);
        $users = [];
        for ($j = 0; $j < $userCount; $j++) {
            $users[] = User::factory()->create(['role' => 'mahasiswa']);
        }

        // Generate location coordinates
        $locationCoords = generateIndonesiaCoords();
        $radius = fake()->randomFloat(2, 100, 500);

        // Create active schedule
        $schedule = createActiveSchedule($locationCoords, $radius);

        // Each user submits attendance
        foreach ($users as $user) {
            $result = $this->attendanceService->processAttendance(
                $user,
                $schedule->id,
                $locationCoords['latitude'],
                $locationCoords['longitude']
            );

            expect($result['success'])->toBeTrue();
        }

        // Verify each user has exactly one record
        foreach ($users as $user) {
            $recordCount = Attendance::where('user_id', $user->id)
                ->where('schedule_id', $schedule->id)
                ->count();

            expect($recordCount)->toBe(1);
        }

        // Verify total records equals user count
        $totalRecords = Attendance::where('schedule_id', $schedule->id)->count();
        expect($totalRecords)->toBe($userCount);
    }
})->group('property');
