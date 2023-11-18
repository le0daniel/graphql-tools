<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper;

use GraphQL\Executor\ExecutionResult;
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

    public function testCreate()
    {
        $extension = $this->prophesize(Extension::class);
        $extension->isEnabled()->willReturn(true);
        $extension->priority()->willReturn(1);

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
        $startEvent = StartEvent::create('', $this->prophesize(GraphQlContext::class)->reveal(), null);
        $endEvent = EndEvent::create(new ExecutionResult(null));

        /** @var Extension|ObjectProphecy $extensionProphecy */
        $extensions = [];

        for ($i = 0; $i < 2; $i++) {
            $extensionProphecy = $this->prophesize(Extension::class);
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
            $this->prophesize(GraphQlContext::class)->reveal(),
            [
                fn() => $disabledExtension->reveal(),
                fn() => $enabledExtension->reveal(),
            ]
        );
        self::assertCount(1, $extensions->getKeyedExtensions());
    }

    public function testMiddlewareFieldResolution()
    {
        /** @var Extension $extension */
        $extension = $this->prophesize(Extension::class);
        $extension->visitField(Argument::type(VisitFieldEvent::class))->willReturn(fn() => 'value');
        $extension->priority()->willReturn(1);
        $extension->isEnabled()->willReturn(true);
        $extension->getName()->willReturn('something');

        $extensions = Extensions::createFromExtensionFactories(
            $this->prophesize(GraphQlContext::class)->reveal(),
            [
                fn() => $extension->reveal()
            ]
        );

        $next = $extensions->willResolveField(VisitFieldEvent::create(
            null, [], ResolveInfoDummy::withDefaults(), []
        ));

        self::assertEquals('other value', $next('other value'));
    }
}
