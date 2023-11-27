<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper;

use GraphQL\Executor\ExecutionResult;
use GraphQlTools\Contract\Extension\InteractsWithFieldResolution;
use GraphQlTools\Contract\Extension\ListensToLifecycleEvents;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Data\ValueObjects\Events\EndEvent;
use GraphQlTools\Data\ValueObjects\Events\StartEvent;
use GraphQlTools\Data\ValueObjects\Events\VisitFieldEvent;
use GraphQlTools\Helper\Extension\ActualCostExtension;
use GraphQlTools\Helper\Extension\Extension;
use GraphQlTools\Helper\Extensions;
use GraphQlTools\Test\Dummies\ResolveInfoDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class ExtensionManagerTest extends TestCase
{
    use ProphecyTrait;

    private function context(): GraphQlContext {
        return $this->prophesize(GraphQlContext::class)->reveal();
    }

    public function testOrdering() {
        $extensions = [];
        for ($i = 0; $i <= 2; $i++) {
            $extensionProphecy = $this->prophesize(Extension::class);
            $extensionProphecy->getName()->willReturn("index-{$i}");
            $extensionProphecy->isEnabled()->willReturn(true);
            $extensionProphecy->priority()->willReturn(20 - $i);
            $extensions[] = fn() => $extensionProphecy->reveal();
        }

        $manager = Extensions::createFromExtensionFactories($this->context(), $extensions);
        self::assertEquals(['index-2', 'index-1', 'index-0'], array_keys($manager->getArray()));
    }

    public function testCreate()
    {
        $extension = $this->prophesize(Extension::class);
        $extension->isEnabled()->willReturn(true);
        $extension->priority()->willReturn(1);
        $extension->getName()->willReturn('something');

        Extensions::createFromExtensionFactories(
            $this->prophesize(GraphQlContext::class)->reveal(),
            [
                ActualCostExtension::class,
                fn() => $extension->reveal(),
            ]);
        self::assertTrue(true);
    }

    public function testExtensionsEventDispatching()
    {
        $startEvent = new StartEvent('', $this->prophesize(GraphQlContext::class)->reveal(), null);
        $endEvent = new EndEvent(new ExecutionResult(null));

        /** @var Extension|ObjectProphecy $extensionProphecy */
        $extensions = [];

        for ($i = 0; $i < 2; $i++) {
            $extensionProphecy = $this->prophesize(Extension::class)->willImplement(ListensToLifecycleEvents::class);
            $extensionProphecy->start($startEvent)->shouldBeCalledOnce();
            $extensionProphecy->end($endEvent)->shouldBeCalledOnce();
            $extensionProphecy->getName()->willReturn(bin2hex(random_bytes(16)));
            $extensions[] = $extensionProphecy->reveal();
        }

        $extensionManager = new Extensions(...$extensions);
        $extensionManager->dispatch($startEvent);
        $extensionManager->dispatch($endEvent);
    }

    public function testDisabledExtension(): void
    {
        $enabledExtension = $this->prophesize(Extension::class);
        $enabledExtension->isEnabled()->willReturn(true);
        $enabledExtension->priority()->willReturn(1);
        $enabledExtension->getName()->willReturn(bin2hex(random_bytes(16)));

        $disabledExtension = $this->prophesize(Extension::class);
        $disabledExtension->isEnabled()->willReturn(false);
        $disabledExtension->getName()->willReturn(bin2hex(random_bytes(16)));


        $extensions = Extensions::createFromExtensionFactories(
            $this->context(),
            [
                fn() => $disabledExtension->reveal(),
                fn() => $enabledExtension->reveal(),
            ]
        );
        self::assertCount(1, $extensions->getArray());
    }

    public function testMiddlewareFieldResolution()
    {
        /** @var Extension $extension */
        $extension = $this->prophesize(Extension::class)->willImplement(InteractsWithFieldResolution::class);
        $extension->visitField(Argument::type(VisitFieldEvent::class))->will(function() {});
        $extension->priority()->willReturn(1);
        $extension->isEnabled()->willReturn(true);
        $extension->getName()->willReturn('something');

        $extensions = Extensions::createFromExtensionFactories(
            $this->context(),
            [
                fn() => $extension->reveal()
            ]
        );

        /** @var VisitFieldEvent $event */
        $extensions->willResolveField($event = new VisitFieldEvent(
            null, [], ResolveInfoDummy::withDefaults(), false, false
        ));

        self::assertEquals('other value', $event->resolveValue('other value'));
    }
}
