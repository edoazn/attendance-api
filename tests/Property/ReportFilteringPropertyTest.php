<?php

/**
 * Property 13: Report Filtering Correctness
 * 
 * Feature: api-absensi-geolocation, Property 13: Report Filtering Correctness
 * 
 * For any attendance report with date range filter, all returned records should have 
 * created_at within the specified range. For any report with schedule_id filter, 
 * all returned records should match that schedule_id.
 * 
 * Validates: Requirements 7.2, 7.3
 */

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Location;
use App\Models\Schedule;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;

beforeEach(function () {
    $this->reportService = new ReportService();
});

/**
 * Helper function to create test attendance data
 */
function createTestAttendanceData(int $count = 10): array
{
    $location = Location::create([
        'name' => fake()->company(),
        'latitude' => fake()->latitude(-8, -6),
        'longitude' => fake()->longitude(106, 108),
        'radius' => fake()->numberBetween(50, 500),
    ]);

    $course = Course::create([
        'course_name' => fake()->sentence(3),
        'course_code' => fake()->unique()->regexify('[A-Z]{2}[0-9]{3}'),
        'lecturer_name' => fake()->name(),
        'location' => fake()->address(),
    ]);

    $schedule = Schedule::create([
        'course_id' => $course->id,
        'location_id' => $location->id,
        'start_time' => Carbon::now()->subHour(),
        'end_time' => Carbon::now()->addHour(),
    ]);

    $user = User::factory()->create(['role' => 'mahasiswa']);

    $attendances = [];
    for ($i = 0; $i < $count; $i++) {
        $attendances[] = Attendance::create([
            'user_id' => $user->id,
            'schedule_id' => $schedule->id,
            'latitude' => fake()->latitude(-8, -6),
            'longitude' => fake()->longitude(106, 108),
            'distance' => fake()->randomFloat(2, 0, 1000),
            'status' => fake()->randomElement(['hadir', 'ditolak']),
            'created_at' => Carbon::now()->subDays(fake()->numberBetween(0, 30)),
        ]);
    }

    return [
        'schedule' => $schedule,
        'user' => $user,
        'attendances' => $attendances,
    ];
}

/**
 * Property 13.1: Date range filter returns only records within range
 * For any date range filter, all returned records must have created_at within that range
 */
test('Property 13.1: Date range filter returns only records within specified range', function () {
    for ($i = 0; $i < 100; $i++) {
        // Clean up previous test data
        Attendance::query()->delete();
        Schedule::query()->delete();
        Course::query()->delete();
        Location::query()->delete();
        User::query()->delete();

        // Create test data with various dates
        createTestAttendanceData(15);

        // Generate random date range
        $startDate = Carbon::now()->subDays(fake()->numberBetween(10, 20))->format('Y-m-d');
        $endDate = Carbon::now()->subDays(fake()->numberBetween(0, 9))->format('Y-m-d');

        $result = $this->reportService->getAttendanceReport($startDate, $endDate, null);

        // Verify all returned records are within the date range
        foreach ($result as $attendance) {
            $createdDate = Carbon::parse($attendance->created_at)->format('Y-m-d');
            expect($createdDate)->toBeGreaterThanOrEqual($startDate);
            expect($createdDate)->toBeLessThanOrEqual($endDate);
        }
    }
})->group('property');

/**
 * Property 13.2: Schedule ID filter returns only records for that schedule
 * For any schedule_id filter, all returned records must match that schedule_id
 */
test('Property 13.2: Schedule ID filter returns only records for specified schedule', function () {
    for ($i = 0; $i < 100; $i++) {
        // Clean up previous test data
        Attendance::query()->delete();
        Schedule::query()->delete();
        Course::query()->delete();
        Location::query()->delete();
        User::query()->delete();

        // Create multiple schedules with attendances
        $data1 = createTestAttendanceData(5);
        
        // Reset unique faker for course_code
        fake()->unique(true);
        
        $data2 = createTestAttendanceData(5);

        // Pick one schedule to filter by
        $targetScheduleId = $data1['schedule']->id;

        $result = $this->reportService->getAttendanceReport(null, null, $targetScheduleId);

        // Verify all returned records match the schedule_id
        foreach ($result as $attendance) {
            expect($attendance->schedule_id)->toBe($targetScheduleId);
        }

        // Verify we got the expected count
        expect($result->count())->toBe(5);
    }
})->group('property');

/**
 * Property 13.3: Combined filters work correctly
 * For any combination of date range and schedule_id filters, all returned records must satisfy both conditions
 */
test('Property 13.3: Combined date range and schedule ID filters work correctly', function () {
    for ($i = 0; $i < 100; $i++) {
        // Clean up previous test data
        Attendance::query()->delete();
        Schedule::query()->delete();
        Course::query()->delete();
        Location::query()->delete();
        User::query()->delete();

        // Create test data
        $data = createTestAttendanceData(15);
        $targetScheduleId = $data['schedule']->id;

        // Generate random date range
        $startDate = Carbon::now()->subDays(fake()->numberBetween(15, 25))->format('Y-m-d');
        $endDate = Carbon::now()->subDays(fake()->numberBetween(0, 14))->format('Y-m-d');

        $result = $this->reportService->getAttendanceReport($startDate, $endDate, $targetScheduleId);

        // Verify all returned records satisfy both conditions
        foreach ($result as $attendance) {
            // Check schedule_id
            expect($attendance->schedule_id)->toBe($targetScheduleId);
            
            // Check date range
            $createdDate = Carbon::parse($attendance->created_at)->format('Y-m-d');
            expect($createdDate)->toBeGreaterThanOrEqual($startDate);
            expect($createdDate)->toBeLessThanOrEqual($endDate);
        }
    }
})->group('property');

/**
 * Property 13.4: No filters returns all records
 * When no filters are applied, all attendance records should be returned
 */
test('Property 13.4: No filters returns all attendance records', function () {
    for ($i = 0; $i < 100; $i++) {
        // Clean up previous test data
        Attendance::query()->delete();
        Schedule::query()->delete();
        Course::query()->delete();
        Location::query()->delete();
        User::query()->delete();

        // Create test data with random count
        $expectedCount = fake()->numberBetween(5, 15);
        createTestAttendanceData($expectedCount);

        $result = $this->reportService->getAttendanceReport(null, null, null);

        // Verify all records are returned
        expect($result->count())->toBe($expectedCount);
    }
})->group('property');
