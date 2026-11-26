<?php

declare(strict_types=1);

namespace Sempico\RestApi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sempico\RestApi\DeliveryReport;

final class DeliveryReportTest extends TestCase
{
    public function testParsesListOfReports(): void
    {
        $body = '[{"id_state":1,"phone":"380501234567","state":"DELIVRD"},{"id_state":2,"state":"UNDELIV","error":1}]';

        $reports = DeliveryReport::parse($body);

        self::assertCount(2, $reports);
        self::assertSame('DELIVRD', $reports[0]['state']);
        self::assertSame(1, $reports[1]['error']);
    }

    public function testWrapsSingleReport(): void
    {
        self::assertSame([['id_state' => 7, 'state' => 'EXPIRED']], DeliveryReport::parse('{"id_state":7,"state":"EXPIRED"}'));
    }

    public function testInvalidBodyGivesEmptyList(): void
    {
        self::assertSame([], DeliveryReport::parse(''));
        self::assertSame([], DeliveryReport::parse('not json'));
        self::assertSame([], DeliveryReport::parse('"string"'));
    }

    public function testKeyCheck(): void
    {
        self::assertTrue(DeliveryReport::isValidKey('secret', 'secret'));
        self::assertFalse(DeliveryReport::isValidKey('wrong', 'secret'));
        self::assertFalse(DeliveryReport::isValidKey(null, 'secret'));
        self::assertFalse(DeliveryReport::isValidKey('', ''), 'an empty expected key must never match');
    }

    public function testConfirm(): void
    {
        self::assertSame('[1,2]', DeliveryReport::confirm([['id_state' => 1], ['id_state' => 2]]));
        self::assertSame('[]', DeliveryReport::confirm([]));
    }
}
