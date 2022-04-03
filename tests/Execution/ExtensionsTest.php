<?php declare(strict_types=1);

namespace GraphQlTools\Test\Execution;

use GraphQL\Executor\ExecutionResult;
use GraphQlTools\Contract\Extension;
use GraphQlTools\Events\EndEvent;
use GraphQlTools\Events\StartEvent;
use GraphQlTools\Events\VisitFieldEvent;
use GraphQlTools\Helper\Extension\FieldMessages;
use GraphQlTools\Helper\Extensions;
use GraphQlTools\Helper\Extension\Tracing;
use GraphQlTools\Test\Dummies\ResolveInfoDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class ExtensionsTest extends TestCase
{
    use ProphecyTrait;

    public function testCreate()
    {
        Extensions::createFromExtensionFactories([
            Tracing::class,
            fn() => new Tracing()
        ]);
        self::assertTrue(true);
    }

    public function testExtensionValidationRules() {
        self::assertEmpty((new Extensions())->collectValidationRules());
        self::assertCount(1, (new Extensions(new FieldMessages()))->collectValidationRules());
    }

    public function testExtensionsEventDispatching() {
        $startEvent = StartEvent::create('');
        $endEvent = EndEvent::create(new ExecutionResult(null));

        /** @var Extension|ObjectProphecy $extensionProphecy */
        $extensions = [];

        for ($i = 0; $i < 2; $i++) {
            $extensionProphecy = $this->prophesize(Extension::class);
            $extensionProphecy->start($startEvent)->shouldBeCalledOnce();
            $extensionProphecy->end($endEvent)->shouldBeCalledOnce();
            $extensions[] = $extensionProphecy->reveal();
        }

        $extensionManager = new Extensions(...$extensions);
        $extensionManager->dispatchStartEvent($startEvent);
        $extensionManager->dispatchEndEvent($endEvent);
    }

    public function testMiddlewareFieldResolution()
    {
        /** @var Extension $extension */
        $extension = $this->prophesize(Extension::class);
        $extension->visitField(Argument::type(VisitFieldEvent::class))->willReturn(fn() => 'value');
        $extension->priority()->willReturn(1);

        $extensions = Extensions::createFromExtensionFactories([
            fn() => $extension->reveal()
        ]);

        $next = $extensions->willVisitField(VisitFieldEvent::create(
            null, [], ResolveInfoDummy::withDefaults(), []
        ));

        self::assertEquals('other value', $next('other value'));
    }
}
