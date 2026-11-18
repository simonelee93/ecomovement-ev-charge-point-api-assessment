<?php

declare(strict_types=1);

use App\Domain\ChargePoint\ChargePointValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ChargePointValidatorTest extends TestCase
{
    #[DataProvider('validChargePoints')]
    public function testItAcceptsValidData(string $name, float $latitude, float $longitude): void
    {
        $this->assertNull(ChargePointValidator::validate($name, $latitude, $longitude));
    }

    public static function validChargePoints(): array
    {
        return [
            'Amsterdam' => ['Amsterdam Central', 52.378, 4.9],
            'zero coordinates' => ['Equator', 0.0, 0.0],
            'minimum coordinates' => ['Southwest', -90.0, -180.0],
            'maximum coordinates' => ['Northeast', 90.0, 180.0],
            'negative coordinates' => ['Southern station', -33.9, -70.6],
            'surrounding whitespace' => ['  Amsterdam Central  ', 52.378, 4.9],
            'Unicode name' => ['Ladestation Köln', 50.94, 6.96],
            'numeric name' => ['0', 0.0, 0.0],
        ];
    }

    #[DataProvider('emptyNames')]
    public function testItRejectsAnEmptyName(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Name cannot be empty.');

        ChargePointValidator::validate($name, 52.378, 4.9);
    }

    public static function emptyNames(): array
    {
        return [
            'empty string' => [''],
            'spaces' => ['   '],
            'tabs and newlines' => ["\t\n\r "],
        ];
    }

    #[DataProvider('invalidLatitudes')]
    public function testItRejectsOutOfRangeLatitude(float $latitude): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Latitude must be between -90 and 90.');

        ChargePointValidator::validate('Amsterdam Central', $latitude, 4.9);
    }

    public static function invalidLatitudes(): array
    {
        return [
            'below minimum' => [-90.0001],
            'above maximum' => [90.0001],
            'negative infinity' => [-INF],
            'positive infinity' => [INF],
        ];
    }

    #[DataProvider('invalidLongitudes')]
    public function testItRejectsOutOfRangeLongitude(float $longitude): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Longitude must be between -180 and 180.');

        ChargePointValidator::validate('Amsterdam Central', 52.378, $longitude);
    }

    public static function invalidLongitudes(): array
    {
        return [
            'below minimum' => [-180.0001],
            'above maximum' => [180.0001],
            'negative infinity' => [-INF],
            'positive infinity' => [INF],
        ];
    }
}
