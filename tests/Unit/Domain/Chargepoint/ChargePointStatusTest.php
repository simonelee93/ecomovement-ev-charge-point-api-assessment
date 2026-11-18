<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Domain\ChargePoint\ChargePointStatus;

class ChargePointStatusTest extends TestCase
{
    public function testItDefinesAllSupportedStatuses(): void
    {
        $statuses = ChargePointStatus::cases();

        $this->assertCount(3, $statuses);

        $this->assertSame(
            ['AVAILABLE', 'OCCUPIED', 'OFFLINE'],
            array_map(
                fn (ChargePointStatus $status): string => $status->value,
                $statuses,
            ),
        );
    }

    public function testItCanCreateAStatusFromItsStringValue(): void
    {
        $status = ChargePointStatus::from('AVAILABLE');

        $this->assertSame(ChargePointStatus::AVAILABLE, $status);
    }

    public function testItRejectsAnUnsupportedStatus(): void
    {
        $this->expectException(ValueError::class);

        ChargePointStatus::from('BROKEN');
    }
}
