<?php

declare(strict_types=1);

use App\Domain\ChargePoint\ChargePointStatus;
use App\Http\ChargePointInputParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ChargePointInputParserTest extends TestCase
{
    public function testItConvertsCreateInputIntoDomainValues(): void
    {
        $input = (new ChargePointInputParser())->parseCreate(
            '{"name":"Amsterdam Central","latitude":"52.378","longitude":4.9,"status":"OCCUPIED"}'
        );

        $this->assertSame('Amsterdam Central', $input->getName());
        $this->assertSame(52.378, $input->getLatitude());
        $this->assertSame(4.9, $input->getLongitude());
        $this->assertSame(ChargePointStatus::OCCUPIED, $input->getStatus());
    }

    #[DataProvider('invalidCreateBodies')]
    public function testItRejectsInvalidCreateInput(string $body, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        (new ChargePointInputParser())->parseCreate($body);
    }

    public static function invalidCreateBodies(): array
    {
        return [
            'malformed JSON' => ['{', 'Invalid request data'],
            'scalar JSON' => ['42', 'Invalid request data'],
            'missing fields' => ['{"name":"Station"}', 'Invalid request data'],
            'wrong name type' => ['{"name":42,"latitude":0,"longitude":0,"status":"AVAILABLE"}', 'Invalid request data'],
            'array coordinate' => ['{"name":"Station","latitude":[],"longitude":0,"status":"AVAILABLE"}', 'Invalid request data'],
            'null coordinate' => ['{"name":"Station","latitude":null,"longitude":0,"status":"AVAILABLE"}', 'Invalid request data'],
            'unsupported status' => ['{"name":"Station","latitude":0,"longitude":0,"status":"BROKEN"}', 'Invalid request data'],
            'empty name' => ['{"name":" ","latitude":0,"longitude":0,"status":"AVAILABLE"}', 'Name cannot be empty.'],
            'latitude range' => ['{"name":"Station","latitude":91,"longitude":0,"status":"AVAILABLE"}', 'Latitude must be between -90 and 90.'],
        ];
    }

    public function testItConvertsNearbyQueryStringsToNumericSearchValues(): void
    {
        $search = (new ChargePointInputParser())->parseNearby(['lat' => '52.378', 'lon' => '4.9', 'radius' => '5']);

        $this->assertSame(52.378, $search->latitude);
        $this->assertSame(4.9, $search->longitude);
        $this->assertSame(5.0, $search->radius);
    }

    #[DataProvider('invalidQueries')]
    public function testItRejectsInvalidNearbyQueryShapes(array $query): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid search parameters');

        (new ChargePointInputParser())->parseNearby($query);
    }

    public static function invalidQueries(): array
    {
        return [
            'missing fields' => [[]],
            'nonnumeric latitude' => [['lat' => 'north', 'lon' => '0', 'radius' => '5']],
            'array radius' => [['lat' => '0', 'lon' => '0', 'radius' => ['5']]],
            'null longitude' => [['lat' => '0', 'lon' => null, 'radius' => '5']],
            'latitude out of range' => [['lat' => '91', 'lon' => '0', 'radius' => '5']],
            'longitude out of range' => [['lat' => '0', 'lon' => '181', 'radius' => '5']],
            'negative radius' => [['lat' => '0', 'lon' => '0', 'radius' => '-5']],
            'nonnumeric radius' => [['lat' => '0', 'lon' => '0', 'radius' => 'five']],
        ];
    }

    public function testItParsesAPositiveId(): void
    {
        $this->assertSame(123, (new ChargePointInputParser())->parseId('123'));
    }

    #[DataProvider('invalidIds')]
    public function testItRejectsInvalidIds(mixed $id): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid charge point ID');

        (new ChargePointInputParser())->parseId($id);
    }

    public static function invalidIds(): array
    {
        return [[null], ['0'], ['-1'], ['1.5'], ['abc'], [[]]];
    }
}
