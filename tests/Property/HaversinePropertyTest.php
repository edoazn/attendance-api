<?php

/**
 * Property 6: Haversine Distance Calculation Correctness
 * 
 * Feature: api-absensi-geolocation, Property 6: Haversine Distance Calculation Correctness
 * 
 * For any two valid coordinate pairs (lat1, lon1) and (lat2, lon2), the Haversine formula should:
 * - Return 0 when coordinates are identical
 * - Return the same result regardless of calculation order (symmetric)
 * - Satisfy triangle inequality: distance(A,B) + distance(B,C) >= distance(A,C)
 * - Produce consistent results for the same inputs (deterministic)
 * 
 * Validates: Requirements 8.1, 8.5, 4.5
 */

use App\Services\GeolocationService;

beforeEach(function () {
    $this->service = new GeolocationService();
});

/**
 * Helper function to generate random valid coordinates
 */
function generateRandomCoordinates(): array
{
    return [
        'latitude' => fake()->latitude(-90, 90),
        'longitude' => fake()->longitude(-180, 180),
    ];
}

/**
 * Property: Identity - distance(A, A) == 0
 * For any coordinate point, the distance to itself should be 0
 */
test('Property 6.1: Identity - distance to same point is zero', function () {
    for ($i = 0; $i < 100; $i++) {
        $coords = generateRandomCoordinates();
        
        $distance = $this->service->calculateDistance(
            $coords['latitude'],
            $coords['longitude'],
            $coords['latitude'],
            $coords['longitude']
        );
        
        expect($distance)->toBe(0.0);
    }
})->group('property');

/**
 * Property: Symmetry - distance(A, B) == distance(B, A)
 * Distance calculation should be symmetric
 */
test('Property 6.2: Symmetry - distance(A,B) equals distance(B,A)', function () {
    for ($i = 0; $i < 100; $i++) {
        $coordsA = generateRandomCoordinates();
        $coordsB = generateRandomCoordinates();
        
        $distanceAB = $this->service->calculateDistance(
            $coordsA['latitude'],
            $coordsA['longitude'],
            $coordsB['latitude'],
            $coordsB['longitude']
        );
        
        $distanceBA = $this->service->calculateDistance(
            $coordsB['latitude'],
            $coordsB['longitude'],
            $coordsA['latitude'],
            $coordsA['longitude']
        );
        
        // Allow for floating point precision issues
        expect(abs($distanceAB - $distanceBA))->toBeLessThan(0.0001);
    }
})->group('property');

/**
 * Property: Triangle Inequality - distance(A,B) + distance(B,C) >= distance(A,C)
 * The sum of two sides of a triangle must be greater than or equal to the third side
 */
test('Property 6.3: Triangle inequality holds', function () {
    for ($i = 0; $i < 100; $i++) {
        $coordsA = generateRandomCoordinates();
        $coordsB = generateRandomCoordinates();
        $coordsC = generateRandomCoordinates();
        
        $distanceAB = $this->service->calculateDistance(
            $coordsA['latitude'],
            $coordsA['longitude'],
            $coordsB['latitude'],
            $coordsB['longitude']
        );
        
        $distanceBC = $this->service->calculateDistance(
            $coordsB['latitude'],
            $coordsB['longitude'],
            $coordsC['latitude'],
            $coordsC['longitude']
        );
        
        $distanceAC = $this->service->calculateDistance(
            $coordsA['latitude'],
            $coordsA['longitude'],
            $coordsC['latitude'],
            $coordsC['longitude']
        );
        
        // Triangle inequality: AB + BC >= AC (with small epsilon for floating point)
        expect($distanceAB + $distanceBC + 0.0001)->toBeGreaterThanOrEqual($distanceAC);
    }
})->group('property');

/**
 * Property: Deterministic - same inputs produce same outputs
 * Calling the function multiple times with same inputs should return same result
 */
test('Property 6.4: Deterministic - same inputs produce same outputs', function () {
    for ($i = 0; $i < 100; $i++) {
        $coordsA = generateRandomCoordinates();
        $coordsB = generateRandomCoordinates();
        
        $distance1 = $this->service->calculateDistance(
            $coordsA['latitude'],
            $coordsA['longitude'],
            $coordsB['latitude'],
            $coordsB['longitude']
        );
        
        $distance2 = $this->service->calculateDistance(
            $coordsA['latitude'],
            $coordsA['longitude'],
            $coordsB['latitude'],
            $coordsB['longitude']
        );
        
        expect($distance1)->toBe($distance2);
    }
})->group('property');

/**
 * Property: Non-negative - distance is always >= 0
 * Distance should never be negative
 */
test('Property 6.5: Non-negative - distance is always greater than or equal to zero', function () {
    for ($i = 0; $i < 100; $i++) {
        $coordsA = generateRandomCoordinates();
        $coordsB = generateRandomCoordinates();
        
        $distance = $this->service->calculateDistance(
            $coordsA['latitude'],
            $coordsA['longitude'],
            $coordsB['latitude'],
            $coordsB['longitude']
        );
        
        expect($distance)->toBeGreaterThanOrEqual(0);
    }
})->group('property');

/**
 * Property: isWithinRadius consistency with calculateDistance
 * isWithinRadius should return true iff calculateDistance <= radius
 */
test('Property 6.6: isWithinRadius is consistent with calculateDistance', function () {
    for ($i = 0; $i < 100; $i++) {
        $coordsA = generateRandomCoordinates();
        $coordsB = generateRandomCoordinates();
        $radius = fake()->randomFloat(2, 1, 20000000); // 1m to 20000km
        
        $distance = $this->service->calculateDistance(
            $coordsA['latitude'],
            $coordsA['longitude'],
            $coordsB['latitude'],
            $coordsB['longitude']
        );
        
        $isWithin = $this->service->isWithinRadius(
            $coordsA['latitude'],
            $coordsA['longitude'],
            $coordsB['latitude'],
            $coordsB['longitude'],
            $radius
        );
        
        expect($isWithin)->toBe($distance <= $radius);
    }
})->group('property');
