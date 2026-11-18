<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\GeoCalculator;
use PHPUnit\Framework\TestCase;

class GeoCalculatorTest extends TestCase
{
    public function testTheDistanceToTheSamePointIsZero(): void
    {
        $this->assertSame(0.0, (new GeoCalculator())->distanceKm(52.378, 4.9, 52.378, 4.9));
    }

    public function testItCalculatesDistanceInKilometres(): void
    {
        $calculator = new GeoCalculator();

        $this->assertEqualsWithDelta(111.195, $calculator->distanceKm(0, 0, 0, 1), 0.001);
        $this->assertEqualsWithDelta(111.195, $calculator->distanceKm(0, 1, 0, 0), 0.001);
    }

    public function testItCalculatesTheShortDistanceAcrossTheDateLine(): void
    {
        $this->assertEqualsWithDelta(
            2.224,
            (new GeoCalculator())->distanceKm(0, 179.99, 0, -179.99),
            0.001,
        );
    }

    public function testItCalculatesDistanceBetweenAntipodalPoints(): void
    {
        $this->assertEqualsWithDelta(20015.087, (new GeoCalculator())->distanceKm(0, 0, 0, 180), 0.001);
    }

    public function testItCalculatesABoundingBoxAroundTheEquator(): void
    {
        $bounds = (new GeoCalculator())->boundingBox(0, 0, 111.195);

        $this->assertEqualsWithDelta(-1, $bounds['minLatitude'], 0.00001);
        $this->assertEqualsWithDelta(1, $bounds['maxLatitude'], 0.00001);
        $this->assertEqualsWithDelta(-1, $bounds['minLongitude'], 0.00001);
        $this->assertEqualsWithDelta(1, $bounds['maxLongitude'], 0.00001);
        $this->assertFalse($bounds['allLongitudes']);
    }

    public function testABoundingBoxCanExtendAcrossTheDateLine(): void
    {
        $calculator = new GeoCalculator();

        $this->assertGreaterThan(180, $calculator->boundingBox(0, 179.99, 3)['maxLongitude']);
        $this->assertLessThan(-180, $calculator->boundingBox(0, -179.99, 3)['minLongitude']);
    }

    public function testSearchesReachingEitherPoleIncludeAllLongitudes(): void
    {
        $calculator = new GeoCalculator();

        foreach ([89.99, -89.99] as $latitude) {
            $bounds = $calculator->boundingBox($latitude, 10, 3);

            $this->assertTrue($bounds['allLongitudes']);
            $this->assertSame(-180.0, $bounds['minLongitude']);
            $this->assertSame(180.0, $bounds['maxLongitude']);
            $this->assertGreaterThanOrEqual(-90, $bounds['minLatitude']);
            $this->assertLessThanOrEqual(90, $bounds['maxLatitude']);
        }
    }
}
