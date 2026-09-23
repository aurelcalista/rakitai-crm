<?php

namespace App\Services;

class GeoLocationService
{
    /**
     * Earth radius in meters.
     */
    public const EARTH_RADIUS_METERS = 6371000;

    /**
     * Calculate distance in meters between two GPS coordinates using Haversine formula.
     */
    public static function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round(self::EARTH_RADIUS_METERS * $c, 2);
    }

    /**
     * Validate visit coordinates against target location.
     *
     * @param float $clientLat Actual latitude submitted by client
     * @param float $clientLng Actual longitude submitted by client
     * @param float|null $targetLat Target location latitude (e.g. from Sekolah / Perusahaan)
     * @param float|null $targetLng Target location longitude
     * @param float|null $maxRadius Optional override for max radius in meters
     * @return array{
     *   distance_meters: float,
     *   is_verified: bool,
     *   is_outside_radius: bool,
     *   status_lokasi: string,
     *   status_verifikasi: string,
     *   status: string,
     *   target_lat_updated: bool
     * }
     */
    public static function validateVisitLocation(
        float $clientLat,
        float $clientLng,
        ?float $targetLat = null,
        ?float $targetLng = null,
        ?float $maxRadius = null
    ): array {
        $allowedRadius = $maxRadius ?? (float) config('crm.visit_radius_meters', 100);

        // Case 1: Target has no baseline coordinates yet (initial capture)
        if (empty($targetLat) || empty($targetLng)) {
            return [
                'distance_meters'    => 0.0,
                'is_verified'        => true,
                'is_outside_radius'  => false,
                'status_lokasi'      => 'Valid',
                'status_verifikasi'  => 'Valid',
                'status'             => 'Selesai',
                'target_lat_updated' => true,
            ];
        }

        // Case 2: Calculate actual distance to existing target coordinates
        $distance = self::calculateDistance($clientLat, $clientLng, $targetLat, $targetLng);
        $isWithinRadius = $distance <= $allowedRadius;

        return [
            'distance_meters'    => $distance,
            'is_verified'        => $isWithinRadius,
            'is_outside_radius'  => !$isWithinRadius,
            'status_lokasi'      => $isWithinRadius ? 'Valid' : 'Perlu Verifikasi',
            'status_verifikasi'  => $isWithinRadius ? 'Valid' : 'Perlu Verifikasi',
            'status'             => $isWithinRadius ? 'Selesai' : 'Perlu Verifikasi',
            'target_lat_updated' => false,
        ];
    }
}
