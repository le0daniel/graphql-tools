<?php declare(strict_types=1);

namespace GraphQlTools\Test;

use GraphQlTools\Context;
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

            protected function makeInstanceOfDataLoaderExecutor(string $className): ExecutableByDataLoader
            {
                return $this->mocks[$className];
            }
        };
    }

    public function testWithDataLoader()
    {
        $context = $this->contextWithMocks([
            ExecutableByDataLoader::class => $this->prophesize(ExecutableByDataLoader::class)->reveal()
        ]);

        $resolveInfo = ResolveInfoDummy::withDefaults(path: ['brand', 0, 'library']);
        $secondResolveInfo = ResolveInfoDummy::withDefaults(path: ['brand', 1, 'library']);
        $differentResolveInfo = ResolveInfoDummy::withDefaults(path: ['brand', 1, 'id']);

        self::assertSame(
            $context->withDataLoader(ExecutableByDataLoader::class, [], $resolveInfo),
            $context->withDataLoader(ExecutableByDataLoader::class, [], $secondResolveInfo),
        );

        self::assertNotSame(
            $context->withDataLoader(ExecutableByDataLoader::class, [], $resolveInfo),
            $context->withDataLoader(ExecutableByDataLoader::class, [], $differentResolveInfo),
        );
    }
}
