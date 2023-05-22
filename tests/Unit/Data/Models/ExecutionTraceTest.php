<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Data\Models;

use DateTimeImmutable;
use GraphQlTools\Data\ValueObjects\Tracing\ExecutionTrace;
use GraphQlTools\Utility\Time;
use PHPUnit\Framework\TestCase;

class ExecutionTraceTest extends TestCase
{

    private function createTrace(): ExecutionTrace
    {
        return new ExecutionTrace(
            '',
            23023,
            (2 * 1000 * 1000 * 1000) + 23023,
            [],
            [],
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2012-01-03 12:03:24')
        );
    }

    public function testDurationNs()
    {
        $trace = $this->createTrace();
        self::assertEquals(2000000000, $trace->durationInNanoseconds());
        self::assertEquals(2.0, Time::nanoSecondsToSeconds($trace->durationInNanoseconds(), 2));
    }

    public function testEndDateTime()
    {
        $trace = $this->createTrace();
        self::assertEquals(
            '2012-01-03 12:03:26',
            $trace->endDateTime()->format('Y-m-d H:i:s')
        );
    }
}
