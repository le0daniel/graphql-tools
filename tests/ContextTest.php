<?php declare(strict_types=1);

namespace GraphQlTools\Test;

use Closure;
use GraphQlTools\Helper\Context;
use GraphQlTools\Contract\ExecutableByDataLoader;
use GraphQlTools\Test\Dummies\ResolveInfoDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ContextTest extends TestCase
{
    use ProphecyTrait;

    private function contextWithMocks(array $mocks): Context
    {
        return new class ($mocks) extends Context {
            public function __construct(private array $mocks)
            {
            }

            protected function makeInstanceOfDataLoaderExecutor(string $classNameOrLoaderName): Closure|ExecutableByDataLoader
            {
                return $this->mocks[$classNameOrLoaderName];
            }
        };
    }

    public function testWithDataLoader()
    {
        $context = $this->contextWithMocks([
            ExecutableByDataLoader::class => $this->prophesize(ExecutableByDataLoader::class)->reveal(),
            'test' => fn() => null,
        ]);

        self::assertSame(
            $context->withDataLoader(ExecutableByDataLoader::class),
            $context->withDataLoader(ExecutableByDataLoader::class),
        );

        self::assertSame(
            $context->withDataLoader('test'),
            $context->withDataLoader('test'),
        );
    }
}
