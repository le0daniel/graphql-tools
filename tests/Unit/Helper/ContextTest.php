<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper;

use Closure;
use GraphQlTools\Helper\Context;
use GraphQlTools\Contract\ExecutableByDataLoader;
use JsonException;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ContextTest extends TestCase
{
    use ProphecyTrait;

    private function contextWithMocks(array $mocks): Context
    {
        return new class ($mocks) extends Context {
            public function __construct(private readonly array $mocks)
            {
            }

            protected function makeInstanceOfDataLoaderExecutor(string $key, array $arguments): Closure|ExecutableByDataLoader
            {
                return $this->mocks[$key];
            }
        };
    }

    /**
     * @throws JsonException
     */
    public function testWithDataLoader()
    {
        $context = $this->contextWithMocks([
            ExecutableByDataLoader::class => $this->prophesize(ExecutableByDataLoader::class)->reveal(),
            'test' => fn() => null,
        ]);

        self::assertSame(
            $context->dataLoader(ExecutableByDataLoader::class),
            $context->dataLoader(ExecutableByDataLoader::class),
        );

        self::assertSame(
            $context->dataLoader('test'),
            $context->dataLoader('test'),
        );

        self::assertSame(
            $context->dataLoader('test', ['true']),
            $context->dataLoader('test', ['true']),
        );

        self::assertNotSame(
            $context->dataLoader('test'),
            $context->dataLoader('test', ['true']),
        );

        self::assertNotSame(
            $context->dataLoader('test', ['true']),
            $context->dataLoader('test', ['false']),
        );
    }
}
