<?php

declare(strict_types=1);

use App\Domain\ChargePoint\NearbySearch;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NearbySearchTest extends TestCase
{
    public function testItAcceptsCoordinateBoundariesAndZeroRadius(): void
    {
        foreach ([[-90.0, -180.0], [90.0, 180.0]] as [$latitude, $longitude]) {
            $search = new NearbySearch($latitude, $longitude, 0);

            $this->assertSame($latitude, $search->latitude);
            $this->assertSame($longitude, $search->longitude);
            $this->assertSame(0.0, $search->radius);
        }
    }

    #[DataProvider('invalidSearches')]
    public function testItRejectsInvalidSearchValues(float $latitude, float $longitude, float $radius): void
    {
        $this->expectException(InvalidArgumentException::class);

        new NearbySearch($latitude, $longitude, $radius);
    }

    public static function invalidSearches(): array
    {
        return [
            'latitude below minimum' => [-90.1, 0, 5],
            'latitude above maximum' => [90.1, 0, 5],
            'longitude below minimum' => [0, -180.1, 5],
            'longitude above maximum' => [0, 180.1, 5],
            'negative radius' => [0, 0, -1],
            'NaN latitude' => [NAN, 0, 5],
            'infinite latitude' => [INF, 0, 5],
            'NaN longitude' => [0, NAN, 5],
            'infinite longitude' => [0, INF, 5],
            'NaN radius' => [0, 0, NAN],
            'infinite radius' => [0, 0, INF],
        ];
    }
}
