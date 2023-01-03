<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Utility;

use Closure;
use DateTimeImmutable;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Test\Dummies\Enum\Eating;
use GraphQlTools\Utility\Compiling;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;

class CompilingTest extends TestCase
{
    private const VALUE = 'test';
    public const PUBLIC_VALUE = 'test';

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

    /**
     * @param string $expected
     * @param mixed $value
     * @return void
     * @dataProvider parametersToStringDataProvider
     */
    public function testParametersToString(string $expected, Closure $closure): void
    {
        $reflection = new ReflectionFunction($closure);
        self::assertEquals($expected, Compiling::parametersToString(...$reflection->getParameters()));
    }

    protected function parametersToStringDataProvider(): array
    {
        return [
            'Empty Closure' => ['', fn() => null],
            'Non Nullable String type' => ['string $string', fn(string $string) => null],
            'Nullable String' => ['?string $string', fn(?string $string) => null],
            'With default value' => ['?string $string = \'string\'', fn(?string $string = 'string') => null],
            'With enum default value' => ['?\GraphQlTools\Test\Dummies\Enum\Eating $string = \GraphQlTools\Test\Dummies\Enum\Eating::MEAT', fn(?Eating $string = Eating::MEAT) => null],
            'With default value constant' => ['?string $string = \GraphQlTools\Test\Unit\Utility\CompilingTest::VALUE', fn(?string $string = self::VALUE) => null],
            'With public const default value' => ['?string $string = \GraphQlTools\Test\Unit\Utility\CompilingTest::PUBLIC_VALUE', fn(?string $string = CompilingTest::PUBLIC_VALUE) => null],
            'With default value and non null' => ['string $string = \'string\'', fn(string $string = 'string') => null],
            'With default value and non null and custom type' => ['?\GraphQlTools\Contract\TypeRegistry $string = NULL', fn(TypeRegistry $string = null) => null],
            'With intersection type' => ['\GraphQlTools\Contract\TypeRegistry&\GraphQlTools\Utility\Compiling $class', fn(TypeRegistry&Compiling $class) => null],
            'With union type nullable' => ['\GraphQlTools\Contract\TypeRegistry|\GraphQlTools\Utility\Compiling|null $class', fn(TypeRegistry|Compiling|null $class) => null],
        ];
    }

}
