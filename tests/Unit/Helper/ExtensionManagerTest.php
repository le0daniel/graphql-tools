<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper;

use GraphQL\Executor\ExecutionResult;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Events\EndEvent;
use GraphQlTools\Events\StartEvent;
use GraphQlTools\Events\VisitFieldEvent;
use GraphQlTools\Helper\Extension\ActualCostExtension;
use GraphQlTools\Helper\Extension\Extension;
use GraphQlTools\Helper\ExtensionManager;
use GraphQlTools\Test\Dummies\ResolveInfoDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class ExtensionManagerTest extends TestCase
{
    use ProphecyTrait;

    public function testCreate()
    {
        $manager = ExtensionManager::createFromExtensionFactories([
            ActualCostExtension::class,
            fn() => new ActualCostExtension()
        ]);
        self::assertTrue(true);
        self::assertEquals(2, $manager->getExtensionsCount());
    }

    public function testExtensionsEventDispatching() {
        $startEvent = StartEvent::create('', $this->prophesize(GraphQlContext::class)->reveal());
        $endEvent = EndEvent::create(new ExecutionResult(null));

        /** @var Extension|ObjectProphecy $extensionProphecy */
        $extensions = [];

        for ($i = 0; $i < 2; $i++) {
            $extensionProphecy = $this->prophesize(Extension::class);
            $extensionProphecy->start($startEvent)->shouldBeCalledOnce();
            $extensionProphecy->end($endEvent)->shouldBeCalledOnce();
            $extensions[] = $extensionProphecy->reveal();
        }

        $extensionManager = new ExtensionManager(...$extensions);
        $extensionManager->dispatchStartEvent($startEvent);
        $extensionManager->dispatchEndEvent($endEvent);
    }

    public function testDisabledExtension(): void {
        $enabledExtension = $this->prophesize(Extension::class);
        $enabledExtension->isEnabled()->willReturn(true);
        $enabledExtension->priority()->willReturn(1);

        $disabledExtension = $this->prophesize(Extension::class);
        $disabledExtension->isEnabled()->willReturn(false);

        $extensions = ExtensionManager::createFromExtensionFactories([
            fn() => $disabledExtension->reveal(),
            fn() => $enabledExtension->reveal(),
        ]);
        self::assertEquals(1, $extensions->getExtensionsCount());
    }

    public function testMiddlewareFieldResolution()
    {
        /** @var Extension $extension */
        $extension = $this->prophesize(Extension::class);
        $extension->visitField(Argument::type(VisitFieldEvent::class))->willReturn(fn() => 'value');
        $extension->priority()->willReturn(1);
        $extension->isEnabled()->willReturn(true);

        $extensions = ExtensionManager::createFromExtensionFactories([
            fn() => $extension->reveal()
        ]);

        $next = $extensions->willResolveField(VisitFieldEvent::create(
            null, [], ResolveInfoDummy::withDefaults(), []
        ));

        self::assertEquals('other value', $next('other value'));
    }
}
