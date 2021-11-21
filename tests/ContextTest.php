<?php declare(strict_types=1);

namespace GraphQlTools\Test;

use GraphQlTools\Context;
use GraphQlTools\DataLoader\SyncDataLoader;
use GraphQlTools\Test\Dummies\ResolveInfoDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ContextTest extends TestCase
{
    use ProphecyTrait;
    private Context $context;

    protected function setUp(): void
    {
        $this->context = new Context();
    }

    public function testGetDataLoader()
    {
        $resolveInfo = ResolveInfoDummy::withDefaults(path: ['brands', 0]);
        $dataLoaderClassName = $this
            ->prophesize(SyncDataLoader::class)
            ->reveal()::class;

        $dataLoader = $this->context->getDataLoader($resolveInfo, $dataLoaderClassName);
        self::assertSame($dataLoader, $this->context->getDataLoader(
            ResolveInfoDummy::withDefaults(path: ['brands', 2]),
            $dataLoaderClassName
        ));

        self::assertNotSame($dataLoader, $this->context->getDataLoader(
            ResolveInfoDummy::withDefaults(path: ['brands', 2, 'id']),
            $dataLoaderClassName
        ));
    }
}
