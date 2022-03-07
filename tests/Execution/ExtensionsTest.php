<?php declare(strict_types=1);

namespace GraphQlTools\Test\Execution;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\Event;
use GraphQlTools\Contract\Extension;
use GraphQlTools\Events\FieldResolutionEvent;
use GraphQlTools\Events\StartEvent;
use GraphQlTools\Helper\Extensions;
use GraphQlTools\Helper\Extension\Tracing;
use GraphQlTools\Test\Dummies\ResolveInfoDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

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

    public function testMiddlewareFieldResolution()
    {
        $extension = $this->prophesize(Extension::class);
        $extension->visitField(Argument::type(FieldResolutionEvent::class))->willReturn(fn() => 'value');
        $extension->priority()->willReturn(1);

        $extensions = Extensions::createFromExtensionFactories([
            fn() => $extension->reveal()
        ]);

        $next = $extensions->willVisitField(FieldResolutionEvent::create(
            null, [], ResolveInfoDummy::withDefaults(), []
        ));

        self::assertEquals('other value', $next('other value'));
    }
}
