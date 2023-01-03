<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Utility;

use DateTimeImmutable;
use GraphQlTools\Test\Dummies\Enum\Eating;
use GraphQlTools\Utility\Compiling;
use PHPUnit\Framework\TestCase;

class CompilingTest extends TestCase
{

    /**
     * @dataProvider absoluteClassNameDataProvider
     * @return void
     */
    public function testAbsoluteClassName(string $expected, string $className): void
    {
        self::assertEquals($expected, Compiling::absoluteClassName($className));
    }

    protected function absoluteClassNameDataProvider(): array
    {
        return [
            'Relative class name' => ['\\' . self::class, self::class],
            'Absolute class name' => ['\\' . self::class, '\\' . self::class],
        ];
    }

    /**
     * @dataProvider exportVariableDataProvider
     * @return void
     */
    public function testExportVariable(string $expected, mixed $value): void
    {
        self::assertEquals($expected, Compiling::exportVariable($value));
    }

    protected function exportVariableDataProvider(): array
    {
        return [
            'String' => ["'string'", 'string'],
            'DateTime' => ["\DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2021-01-03 08:25:26')", DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2021-01-03 08:25:26')],
            'Enum' => ['\GraphQlTools\Test\Dummies\Enum\Eating::MEAT', Eating::MEAT],
        ];
    }
}
