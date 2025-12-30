<?php

/**
 * Property 4: Location CRUD Round-Trip
 * 
 * Feature: api-absensi-geolocation, Property 4: Location CRUD Round-Trip
 * 
 * For any valid location data (name, latitude in [-90,90], longitude in [-180,180], positive radius),
 * creating a location then retrieving it should return equivalent data.
 * 
 * Validates: Requirements 2.1, 2.2, 2.3
 */

use App\Models\User;
use App\Models\Location;

/**
 * Helper function to generate random valid location data
 */
function generateValidLocationData(): array
{
    return [
        'name' => fake()->company() . ' ' . fake()->buildingNumber(),
        'latitude' => fake()->latitude(-90, 90),
        'longitude' => fake()->longitude(-180, 180),
        'radius' => fake()->numberBetween(50, 500),
    ];
}

/**
 * Helper function to create an admin user
 */
function createAdminUser(): User
{
    return User::create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password123'),
        'role' => 'admin',
    ]);
}

/**
 * Property 4.1: Create location then retrieve returns equivalent data
 * For any valid location data, creating then retrieving should return the same data
 */
test('Property 4.1: Create location then retrieve returns equivalent data', function () {
    for ($i = 0; $i < 100; $i++) {
        $admin = createAdminUser();
        $locationData = generateValidLocationData();

        // Create location
        $createResponse = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/locations', $locationData);

        $createResponse->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'name', 'latitude', 'longitude', 'radius']
            ]);

        $createdId = $createResponse->json('data.id');

        // Retrieve all locations
        $indexResponse = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/locations');

        $indexResponse->assertStatus(200);

        $locations = collect($indexResponse->json('data'));
        $retrievedLocation = $locations->firstWhere('id', $createdId);

        // Verify data equivalence
        expect($retrievedLocation)->not->toBeNull();
        expect($retrievedLocation['name'])->toBe($locationData['name']);
        expect(abs((float) $retrievedLocation['latitude'] - $locationData['latitude']))->toBeLessThan(0.00001);
        expect(abs((float) $retrievedLocation['longitude'] - $locationData['longitude']))->toBeLessThan(0.00001);
        expect(abs((float) $retrievedLocation['radius'] - $locationData['radius']))->toBeLessThan(0.01);

        // Clean up
        Location::destroy($createdId);
        $admin->delete();
    }
})->group('property');

/**
 * Property 4.2: Update location then retrieve returns updated data
 * For any valid location, updating then retrieving should return the updated data
 */
test('Property 4.2: Update location then retrieve returns updated data', function () {
    for ($i = 0; $i < 100; $i++) {
        $admin = createAdminUser();
        $originalData = generateValidLocationData();
        $updatedData = generateValidLocationData();

        // Create location first
        $location = Location::create($originalData);

        // Update location
        $updateResponse = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/locations/{$location->id}", $updatedData);

        $updateResponse->assertStatus(200)
            ->assertJson(['message' => 'Location updated successfully']);

        // Retrieve and verify
        $indexResponse = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/locations');

        $locations = collect($indexResponse->json('data'));
        $retrievedLocation = $locations->firstWhere('id', $location->id);

        expect($retrievedLocation['name'])->toBe($updatedData['name']);
        expect(abs((float) $retrievedLocation['latitude'] - $updatedData['latitude']))->toBeLessThan(0.00001);
        expect(abs((float) $retrievedLocation['longitude'] - $updatedData['longitude']))->toBeLessThan(0.00001);
        expect(abs((float) $retrievedLocation['radius'] - $updatedData['radius']))->toBeLessThan(0.01);

        // Clean up
        $location->delete();
        $admin->delete();
    }
})->group('property');

/**
 * Property 4.3: Index returns all created locations
 * For any set of created locations, index should return all of them
 */
test('Property 4.3: Index returns all created locations', function () {
    for ($i = 0; $i < 100; $i++) {
        $admin = createAdminUser();
        $locationCount = fake()->numberBetween(1, 5);
        $createdIds = [];

        // Create multiple locations
        for ($j = 0; $j < $locationCount; $j++) {
            $locationData = generateValidLocationData();
            $response = $this->actingAs($admin, 'sanctum')
                ->postJson('/api/locations', $locationData);
            
            $createdIds[] = $response->json('data.id');
        }

        // Retrieve all locations
        $indexResponse = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/locations');

        $indexResponse->assertStatus(200);
        $locations = collect($indexResponse->json('data'));

        // Verify all created locations are present
        foreach ($createdIds as $id) {
            expect($locations->contains('id', $id))->toBeTrue();
        }

        // Clean up
        Location::destroy($createdIds);
        $admin->delete();
    }
})->group('property');
