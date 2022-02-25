<?php declare(strict_types=1);

namespace GraphQlTools\Test\Execution;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\Event;
use GraphQlTools\Contract\Extension;
use GraphQlTools\Events\FieldResolutionEvent;
use GraphQlTools\Events\StartEvent;
use GraphQlTools\Execution\Extensions;
use GraphQlTools\Extension\Tracing;
use GraphQlTools\Test\Dummies\ResolveInfoDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class ExtensionsTest extends TestCase
{
    use ProphecyTrait;

    public function testCreate()
    {
        Extensions::create([
            Tracing::class,
            fn() => new Tracing()
        ]);
        self::assertTrue(true);
    }

    public function testMiddlewareFieldResolution()
    {
        $extension = $this->prophesize(Extension::class);
        $extension->fieldResolution(Argument::type(FieldResolutionEvent::class))->willReturn(fn() => 'value');
        $extension->priority()->willReturn(1);

        $extensions = Extensions::create([
            fn() => $extension->reveal()
        ]);

        $next = $extensions->middlewareFieldResolution(FieldResolutionEvent::create(
            null, [], ResolveInfoDummy::withDefaults(), []
        ));

        self::assertEquals('value', $next('other value'));
    }

    public function testDispatch()
    {
        $extensions = new Extensions();
        $extensions->dispatch(StartEvent::create(''));
        self::assertTrue(true);
    }
}
