<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper\Compilation;

use Closure;
use GraphQlTools\Helper\Compilation\ClosureCompiler;
use GraphQlTools\Test\Unit\Data\Models\NamedDummy;
use PHPUnit\Framework\TestCase;
use function _PHPStan_5c71ab23c\RingCentral\Psr7\str;

class ClosureCompilerTest extends TestCase
{
    private ClosureCompiler $compiler;
    protected function setUp(): void
    {
        $this->compiler = new ClosureCompiler();
    }

    private function testMethod(string $test, \DateTimeInterface $date): string {
        return "{$test} {$date->format('Y')}";
    }

    public static function staticMethod(string $test, \DateTimeInterface $date): string {
        return "{$test} {$date->format('Y')}";
    }

    private function testWithSelfAccess(): string {
        return self::class . ' <=> ' . static::class;
    }

    /**
     * @return void
     * @dataProvider compileDataProvider
     */
    public function testCompile(string $expected, Closure $closure): void
    {
        $compiled = $this->compiler->compile($closure);
        self::assertEquals($expected, $compiled);
    }

    public function compileDataProvider(): array {
        return [
            'simple empty closure' => [
                'function () {}', function () {}
            ],
            'closure with arguments' => [
                'function (string $test, \DateTimeInterface $date): void {}',
                function (string $test, \DateTimeInterface $date): void {}
            ],
            'method as closure' => [
                'static function (string $test, \DateTimeInterface $date): string {
        return "{$test} {$date->format(\'Y\')}";
    }',
                $this->testMethod(...)
            ],
            'test with static variables' => [
                'fn() => self::class', fn() => self::class,
            ],
            'test with public static method' => [
                '\GraphQlTools\Test\Unit\Helper\Compilation\ClosureCompilerTest::staticMethod(...)', self::staticMethod(...),
            ],
            'test method with static & self access' => [
                "static function (): string {
        return \GraphQlTools\Test\Unit\Helper\Compilation\ClosureCompilerTest::class . ' <=> ' . \GraphQlTools\Test\Unit\Helper\Compilation\ClosureCompilerTest::class;
    }", $this->testWithSelfAccess(...)
            ],
            // 'test closure with named variables' => [
            //     "fn() => new \GraphQlTools\Test\Unit\Data\Models\NamedDummy(test: 'variable', variable: 'test')\n",
            //     fn() => new NamedDummy(test: 'variable', variable: 'test')
            // ]
        ];
    }
}
