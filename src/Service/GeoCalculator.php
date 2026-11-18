<?php

declare(strict_types=1);

namespace App\Service;

/** Geographic calculations assume valid coordinates and a finite, non-negative radius in kilometres. */
final class GeoCalculator
{
    private const EARTH_RADIUS_KM = 6371.0;

    public function distanceKm(
        float $latitude,
        float $longitude,
        float $pointLatitude,
        float $pointLongitude,
    ): float {
        $latitudeRadians = deg2rad($latitude);
        $pointLatitudeRadians = deg2rad($pointLatitude);
        $latitudeDifference = $pointLatitudeRadians - $latitudeRadians;
        // Use the shortest longitude difference; -180 and 180 are the same meridian.
        $longitudeDifference = deg2rad(fmod($pointLongitude - $longitude + 540.0, 360.0) - 180.0);

        $a = sin($latitudeDifference / 2) ** 2
            + cos($latitudeRadians) * cos($pointLatitudeRadians) * sin($longitudeDifference / 2) ** 2;

        // Clamp rounding errors so the square root and asin remain defined.
        return 2 * self::EARTH_RADIUS_KM * asin(sqrt(max(0.0, min(1.0, $a))));
    }

    /**
     * @return array{
     *     minLatitude: float,
     *     maxLatitude: float,
     *     minLongitude: float,
     *     maxLongitude: float,
     *     allLongitudes: bool
     * }
     */
    public function boundingBox(
        float $latitude,
        float $longitude,
        float $radius
    ): array {
        $angularDistance = $radius / self::EARTH_RADIUS_KM;
        $latitudeRadians = deg2rad($latitude);

        $minLatitudeRadians = max(
            -M_PI / 2,
            $latitudeRadians - $angularDistance
        );

        $maxLatitudeRadians = min(
            M_PI / 2,
            $latitudeRadians + $angularDistance
        );

        $minLatitude = rad2deg($minLatitudeRadians);
        $maxLatitude = rad2deg($maxLatitudeRadians);

        // If the search reaches a pole, every longitude must be considered.
        $allLongitudes = (
            $angularDistance >= M_PI / 2
            || $minLatitudeRadians <= -M_PI / 2
            || $maxLatitudeRadians >= M_PI / 2
        );

        if ($allLongitudes) {
            return [
                'minLatitude' => $minLatitude,
                'maxLatitude' => $maxLatitude,
                'minLongitude' => -180.0,
                'maxLongitude' => 180.0,
                'allLongitudes' => true,
            ];
        }

        $longitudeDifference = rad2deg(
            asin(
                sin($angularDistance) / cos($latitudeRadians)
            )
        );

        $minLongitude = $longitude - $longitudeDifference;
        $maxLongitude = $longitude + $longitudeDifference;

        return [
            'minLatitude' => $minLatitude,
            'maxLatitude' => $maxLatitude,
            'minLongitude' => $minLongitude,
            'maxLongitude' => $maxLongitude,
            'allLongitudes' => false,
        ];
    }

}
