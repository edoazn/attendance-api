<?php

/**
 * Property 11: User-Specific History Retrieval
 * Property 12: History Ordering
 * 
 * Feature: api-absensi-geolocation, Property 11: User-Specific History Retrieval
 * Feature: api-absensi-geolocation, Property 12: History Ordering
 * 
 * Property 11: For any user requesting attendance history, the returned records 
 * should only contain attendances belonging to that user (no other users' records).
 * 
 * Property 12: For any attendance history with multiple records, the records 
 * should be ordered by created_at in descending order (newest first).
 * 
 * Validates: Requirements 5.1, 5.3
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
function cleanupHistoryTestData(): void
{
    Attendance::query()->delete();
    Schedule::query()->delete();
    Course::query()->delete();
    Location::query()->delete();
    User::query()->delete();
}

/**
 * Helper function to create an active schedule for history tests
 */
function createActiveScheduleForHistory(): Schedule
{
    $location = Location::create([
        'name' => fake()->company(),
        'latitude' => fake()->latitude(-8, -6),
        'longitude' => fake()->longitude(106, 108),
        'radius' => fake()->randomFloat(2, 100, 1000),
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
 * Property 11.1: User history contains only their own records
 * For any user requesting attendance history, all returned records must belong to that user
 * 
 * Validates: Requirements 5.1
 */
test('Property 11.1: User history contains only their own records', function () {
    for ($i = 0; $i < 100; $i++) {
        cleanupHistoryTestData();

        // Create multiple users
        $userCount = fake()->numberBetween(2, 5);
        $users = [];
        for ($j = 0; $j < $userCount; $j++) {
            $users[] = User::factory()->create(['role' => 'mahasiswa']);
        }

        // Create multiple schedules
        $scheduleCount = fake()->numberBetween(2, 4);
        $schedules = [];
        for ($j = 0; $j < $scheduleCount; $j++) {
            $schedules[] = createActiveScheduleForHistory();
        }

        // Each user submits attendance to different schedules
        foreach ($users as $user) {
            foreach ($schedules as $schedule) {
                // Get location coordinates for "hadir" status
                $location = $schedule->location;
                $this->attendanceService->processAttendance(
                    $user,
                    $schedule->id,
                    (float) $location->latitude,
                    (float) $location->longitude
                );
            }
        }

        // Pick a random user to test
        $testUser = $users[array_rand($users)];

        // Get history for test user
        $history = $this->attendanceService->getUserHistory($testUser);

        // All records must belong to the test user
        foreach ($history as $attendance) {
            expect($attendance->user_id)->toBe($testUser->id);
        }

        // Count should match the number of schedules (one attendance per schedule)
        expect($history->count())->toBe($scheduleCount);
    }
})->group('property');

/**
 * Property 11.2: User history excludes other users' records
 * For any user, their history must not contain any records from other users
 * 
 * Validates: Requirements 5.1
 */
test('Property 11.2: User history excludes other users records', function () {
    for ($i = 0; $i < 100; $i++) {
        cleanupHistoryTestData();

        // Create exactly 2 users
        $user1 = User::factory()->create(['role' => 'mahasiswa']);
        $user2 = User::factory()->create(['role' => 'mahasiswa']);

        // Create schedules
        $scheduleCount = fake()->numberBetween(2, 4);
        $schedules = [];
        for ($j = 0; $j < $scheduleCount; $j++) {
            $schedules[] = createActiveScheduleForHistory();
        }

        // Both users submit attendance
        foreach ($schedules as $schedule) {
            $location = $schedule->location;
            
            $this->attendanceService->processAttendance(
                $user1,
                $schedule->id,
                (float) $location->latitude,
                (float) $location->longitude
            );

            $this->attendanceService->processAttendance(
                $user2,
                $schedule->id,
                (float) $location->latitude,
                (float) $location->longitude
            );
        }

        // Get history for user1
        $user1History = $this->attendanceService->getUserHistory($user1);

        // Verify no records belong to user2
        foreach ($user1History as $attendance) {
            expect($attendance->user_id)->not->toBe($user2->id);
        }

        // Get history for user2
        $user2History = $this->attendanceService->getUserHistory($user2);

        // Verify no records belong to user1
        foreach ($user2History as $attendance) {
            expect($attendance->user_id)->not->toBe($user1->id);
        }
    }
})->group('property');


/**
 * Property 12.1: History is ordered by created_at descending
 * For any attendance history with multiple records, records must be ordered newest first
 * 
 * Validates: Requirements 5.3
 */
test('Property 12.1: History is ordered by created_at descending', function () {
    for ($i = 0; $i < 100; $i++) {
        cleanupHistoryTestData();

        $user = User::factory()->create(['role' => 'mahasiswa']);

        // Create multiple schedules with different times
        $scheduleCount = fake()->numberBetween(3, 6);
        $schedules = [];
        for ($j = 0; $j < $scheduleCount; $j++) {
            $schedules[] = createActiveScheduleForHistory();
        }

        // Submit attendance to each schedule with slight time delays
        foreach ($schedules as $index => $schedule) {
            $location = $schedule->location;
            
            $this->attendanceService->processAttendance(
                $user,
                $schedule->id,
                (float) $location->latitude,
                (float) $location->longitude
            );

            // Manually update created_at to ensure different timestamps
            $attendance = Attendance::where('user_id', $user->id)
                ->where('schedule_id', $schedule->id)
                ->first();
            
            // Set created_at with increasing time offset
            $attendance->created_at = Carbon::now()->subMinutes($scheduleCount - $index);
            $attendance->save();
        }

        // Get history
        $history = $this->attendanceService->getUserHistory($user);

        // Verify ordering: each record should have created_at >= next record
        $previousCreatedAt = null;
        foreach ($history as $attendance) {
            if ($previousCreatedAt !== null) {
                expect($previousCreatedAt->gte($attendance->created_at))->toBeTrue();
            }
            $previousCreatedAt = $attendance->created_at;
        }
    }
})->group('property');

/**
 * Property 12.2: Newest attendance appears first in history
 * For any user with multiple attendances, the most recent one should be first
 * 
 * Validates: Requirements 5.3
 */
test('Property 12.2: Newest attendance appears first in history', function () {
    for ($i = 0; $i < 100; $i++) {
        cleanupHistoryTestData();

        $user = User::factory()->create(['role' => 'mahasiswa']);

        // Create multiple schedules
        $scheduleCount = fake()->numberBetween(3, 6);
        $schedules = [];
        for ($j = 0; $j < $scheduleCount; $j++) {
            $schedules[] = createActiveScheduleForHistory();
        }

        // Submit attendance and track the newest one
        $newestAttendanceId = null;
        $newestCreatedAt = null;

        foreach ($schedules as $index => $schedule) {
            $location = $schedule->location;
            
            $this->attendanceService->processAttendance(
                $user,
                $schedule->id,
                (float) $location->latitude,
                (float) $location->longitude
            );

            $attendance = Attendance::where('user_id', $user->id)
                ->where('schedule_id', $schedule->id)
                ->first();

            // Set created_at with specific timestamps
            $createdAt = Carbon::now()->addMinutes($index);
            $attendance->created_at = $createdAt;
            $attendance->save();

            // Track the newest one (highest index = newest)
            if ($newestCreatedAt === null || $createdAt->gt($newestCreatedAt)) {
                $newestAttendanceId = $attendance->id;
                $newestCreatedAt = $createdAt;
            }
        }

        // Get history
        $history = $this->attendanceService->getUserHistory($user);

        // First record should be the newest
        expect($history->first()->id)->toBe($newestAttendanceId);
    }
})->group('property');
