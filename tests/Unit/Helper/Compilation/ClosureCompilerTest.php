<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper\Compilation;

use Closure;
use GraphQlTools\Helper\Compilation\ClosureCompiler;
use PHPUnit\Framework\TestCase;

class ClosureCompilerTest extends TestCase
{
    private ClosureCompiler $compiler;
    protected function setUp(): void
    {
        $this->compiler = new ClosureCompiler();
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

    private function testMethod(string $test, \DateTimeInterface $date): string {
        return "{$test} {$date->format('Y')}";
    }

    public static function staticMethod(string $test, \DateTimeInterface $date): string {
        return "{$test} {$date->format('Y')}";
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
        ];
    }
}
