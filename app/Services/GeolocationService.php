<?php

namespace App\Services;

class GeolocationService
{
    /**
     * Earth radius in meters
     */
    private const EARTH_RADIUS = 6371000;

    /**
     * Calculate distance between two coordinate points using Haversine Formula
     *
     * @param float $lat1 Latitude of first point
     * @param float $lon1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lon2 Longitude of second point
     * @return float Distance in meters
     */
    public function calculateDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        // Convert degrees to radians
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);

        // Differences
        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;

        // Haversine formula
        $a = sin($deltaLat / 2) ** 2 +
             cos($lat1Rad) * cos($lat2Rad) * sin($deltaLon / 2) ** 2;
        
        $c = 2 * asin(sqrt($a));

        return self::EARTH_RADIUS * $c;
    }

    /**
     * Check if user coordinates are within the specified radius of a location
     *
     * @param float $userLat User's latitude
     * @param float $userLon User's longitude
     * @param float $locationLat Location's latitude
     * @param float $locationLon Location's longitude
     * @param float $radius Allowed radius in meters
     * @return bool True if within radius, false otherwise
     */
    public function isWithinRadius(
        float $userLat,
        float $userLon,
        float $locationLat,
        float $locationLon,
        float $radius
    ): bool {
        $distance = $this->calculateDistance(
            $userLat,
            $userLon,
            $locationLat,
            $locationLon
        );

        return $distance <= $radius;
    }
}
