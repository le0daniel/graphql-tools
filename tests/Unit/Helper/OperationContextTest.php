<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper;

use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Helper\Extensions;
use GraphQlTools\Helper\OperationContext;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class OperationContextTest extends TestCase
{
    use ProphecyTrait;

    public function testShouldRunAgain()
    {
        $context = new OperationContext(
            $this->prophesize(GraphQlContext::class)->reveal(),
            new Extensions(),
            3
        );

        $context->startRun();
        $context->endRun();
        self::assertFalse($context->shouldRunAgain());

        $context->startRun();
        $context->deferField([2], 'label', []);
        $context->endRun();
        self::assertTrue($context->shouldRunAgain());
        $context->popDeferred([2]);

        $context->startRun();
        $context->deferField([1], 'label', []);
        $context->endRun();
        self::assertFalse($context->shouldRunAgain());
    }

    public function testSetCache()
    {
        $context = new OperationContext(
            $this->prophesize(GraphQlContext::class)->reveal(),
            new Extensions(),
            3
        );

        $result = $context->setCache([2], 'test', [1, 2, 3]);
        self::assertEquals([1, 2, 3], $result);
        self::assertEquals([1, 2, 3], $context->getCache([2], 'test'));
        self::assertNull($context->getCache([2], 'test2'));
    }

    public function testIsFirstRun()
    {
        $context = new OperationContext(
            $this->prophesize(GraphQlContext::class)->reveal(),
            new Extensions(),
            3
        );

        self::assertFalse($context->isFirstRun());
        $context->startRun();
        self::assertTrue($context->isFirstRun());
    }

    public function testSettingAndPoppingDeferred()
    {
        $context = new OperationContext(
            $this->prophesize(GraphQlContext::class)->reveal(),
            new Extensions(),
            3
        );

        self::assertFalse($context->isDeferred([1,2,3]));
        $context->deferField([1,2,3], '', null);
        self::assertTrue($context->isDeferred([1,2,3]));
        $context->popDeferred([1,2,3]);
        self::assertFalse($context->isDeferred([1,2,3]));
    }

    public function testSetResultData()
    {
        $context = new OperationContext(
            $this->prophesize(GraphQlContext::class)->reveal(),
            $this->prophesize(Extensions::class)->reveal(),
            3
        );

        $context->setResultData(null);
        self::assertFalse($context->isInResult(['query', 2, 'items']));
        $context->setResultData(['query' => [2 => ['items' => null]]]);
        self::assertTrue($context->isInResult(['query', 2, 'items']));
        self::assertEquals(null, $context->getFromResult(['query', 2, 'items']));

        $context->setResultData(['query' => [2 => ['items' => 123]]]);
        self::assertTrue($context->isInResult(['query', 2, 'items']));
        self::assertEquals(123, $context->getFromResult(['query', 2, 'items']));
    }
}
