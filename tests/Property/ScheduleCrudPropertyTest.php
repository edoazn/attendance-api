<?php

/**
 * Property 5: Schedule CRUD Round-Trip
 * 
 * Feature: api-absensi-geolocation, Property 5: Schedule CRUD Round-Trip
 * 
 * For any valid schedule data with existing course_id and location_id,
 * creating a schedule then retrieving it should return the schedule with course and location details.
 * 
 * Validates: Requirements 3.1, 3.2
 */

use App\Models\User;
use App\Models\Course;
use App\Models\Location;
use App\Models\Schedule;
use Carbon\Carbon;

/**
 * Helper function to generate random valid schedule data
 */
function generateValidScheduleData(int $courseId, int $locationId): array
{
    $startTime = Carbon::now()->addHours(fake()->numberBetween(1, 24));
    $endTime = $startTime->copy()->addHours(fake()->numberBetween(1, 3));

    return [
        'course_id' => $courseId,
        'location_id' => $locationId,
        'start_time' => $startTime->format('Y-m-d H:i:s'),
        'end_time' => $endTime->format('Y-m-d H:i:s'),
    ];
}

/**
 * Helper function to create an admin user for schedule tests
 */
function createScheduleAdminUser(): User
{
    return User::create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password123'),
        'role' => 'admin',
    ]);
}

/**
 * Helper function to create a course
 */
function createTestCourse(): Course
{
    return Course::create([
        'course_name' => fake()->sentence(3),
        'course_code' => strtoupper(fake()->unique()->lexify('???')) . fake()->numberBetween(100, 999),
        'lecturer_name' => fake()->name(),
        'location_room' => 'Room ' . fake()->buildingNumber(),
    ]);
}

/**
 * Helper function to create a location
 */
function createTestLocation(): Location
{
    return Location::create([
        'name' => fake()->company() . ' Building',
        'latitude' => fake()->latitude(-7, -6),
        'longitude' => fake()->longitude(106, 108),
        'radius' => fake()->numberBetween(50, 200),
    ]);
}

/**
 * Property 5.1: Create schedule then retrieve returns equivalent data with course and location details
 * For any valid schedule data, creating then retrieving should return the same data with relationships
 */
test('Property 5.1: Create schedule then retrieve returns equivalent data with relationships', function () {
    for ($i = 0; $i < 100; $i++) {
        $admin = createScheduleAdminUser();
        $course = createTestCourse();
        $location = createTestLocation();
        $scheduleData = generateValidScheduleData($course->id, $location->id);

        // Create schedule
        $createResponse = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/schedules', $scheduleData);

        $createResponse->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'course_id',
                    'location_id',
                    'start_time',
                    'end_time',
                    'course' => ['id', 'course_name', 'course_code'],
                    'location' => ['id', 'name', 'latitude', 'longitude', 'radius']
                ]
            ]);

        $createdId = $createResponse->json('data.id');

        // Retrieve all schedules
        $indexResponse = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/schedules');

        $indexResponse->assertStatus(200);

        $schedules = collect($indexResponse->json('data'));
        $retrievedSchedule = $schedules->firstWhere('id', $createdId);

        // Verify data equivalence
        expect($retrievedSchedule)->not->toBeNull();
        expect($retrievedSchedule['course_id'])->toBe($scheduleData['course_id']);
        expect($retrievedSchedule['location_id'])->toBe($scheduleData['location_id']);
        
        // Verify course relationship is loaded
        expect($retrievedSchedule['course'])->not->toBeNull();
        expect($retrievedSchedule['course']['id'])->toBe($course->id);
        expect($retrievedSchedule['course']['course_name'])->toBe($course->course_name);

        // Verify location relationship is loaded
        expect($retrievedSchedule['location'])->not->toBeNull();
        expect($retrievedSchedule['location']['id'])->toBe($location->id);
        expect($retrievedSchedule['location']['name'])->toBe($location->name);

        // Clean up
        Schedule::destroy($createdId);
        $course->delete();
        $location->delete();
        $admin->delete();
    }
})->group('property');

/**
 * Property 5.2: Index returns all created schedules with relationships
 * For any set of created schedules, index should return all of them with course and location details
 */
test('Property 5.2: Index returns all created schedules with relationships', function () {
    for ($i = 0; $i < 100; $i++) {
        $admin = createScheduleAdminUser();
        $scheduleCount = fake()->numberBetween(1, 3);
        $createdIds = [];
        $courses = [];
        $locations = [];

        // Create multiple schedules
        for ($j = 0; $j < $scheduleCount; $j++) {
            $course = createTestCourse();
            $location = createTestLocation();
            $courses[] = $course;
            $locations[] = $location;

            $scheduleData = generateValidScheduleData($course->id, $location->id);
            $response = $this->actingAs($admin, 'sanctum')
                ->postJson('/api/schedules', $scheduleData);
            
            $createdIds[] = $response->json('data.id');
        }

        // Retrieve all schedules
        $indexResponse = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/schedules');

        $indexResponse->assertStatus(200);
        $schedules = collect($indexResponse->json('data'));

        // Verify all created schedules are present with relationships
        foreach ($createdIds as $index => $id) {
            $schedule = $schedules->firstWhere('id', $id);
            expect($schedule)->not->toBeNull();
            expect($schedule['course'])->not->toBeNull();
            expect($schedule['location'])->not->toBeNull();
        }

        // Clean up
        Schedule::destroy($createdIds);
        foreach ($courses as $course) {
            $course->delete();
        }
        foreach ($locations as $location) {
            $location->delete();
        }
        $admin->delete();
    }
})->group('property');

/**
 * Property 5.3: Schedule validation rejects invalid end_time
 * For any schedule where end_time is before or equal to start_time, creation should fail
 */
test('Property 5.3: Schedule validation rejects invalid end_time', function () {
    for ($i = 0; $i < 100; $i++) {
        $admin = createScheduleAdminUser();
        $course = createTestCourse();
        $location = createTestLocation();

        $startTime = Carbon::now()->addHours(fake()->numberBetween(1, 24));
        // End time is before start time (invalid)
        $endTime = $startTime->copy()->subHours(fake()->numberBetween(1, 3));

        $invalidScheduleData = [
            'course_id' => $course->id,
            'location_id' => $location->id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'end_time' => $endTime->format('Y-m-d H:i:s'),
        ];

        // Attempt to create schedule with invalid end_time
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/schedules', $invalidScheduleData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_time']);

        // Clean up
        $course->delete();
        $location->delete();
        $admin->delete();
    }
})->group('property');
