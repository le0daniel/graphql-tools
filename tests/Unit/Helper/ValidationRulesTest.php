<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper;

use GraphQlTools\Helper\ValidationRules;
use GraphQL\Validator\Rules\ValidationRule;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Contract\ValidationRule\RequiresVariableValues;
use GraphQlTools\Helper\Validation\CollectDeprecatedFieldNotices;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class ValidationRulesTest extends TestCase
{
    use ProphecyTrait;

    private function prophecy(string $name): ObjectProphecy|ValidationRule {
        $plainValidationRule = $this->prophesize(ValidationRule::class);
        $plainValidationRule->getName()->willReturn($name);
        return $plainValidationRule;
    }

    public function testInitializeWithInstanceGiven()
    {
        $context = $this->prophesize(GraphQlContext::class)->reveal();
        $simple = $this->prophecy('simple')->reveal();

        $inited = ValidationRules::initialize($context, [
            $simple
        ], [])->toArray();

        self::assertSame($simple, $inited['simple']);
    }

    public function testInitializeWithClassNameGiven()
    {
        $context = $this->prophesize(GraphQlContext::class)->reveal();


        $inited = ValidationRules::initialize($context, [
            CollectDeprecatedFieldNotices::class
        ], [])->toArray();

        self::assertInstanceOf(CollectDeprecatedFieldNotices::class, $inited[CollectDeprecatedFieldNotices::class]);
    }

    public function testInitializeWithFactory()
    {
        $context = $this->prophesize(GraphQlContext::class)->reveal();
        $simple = $this->prophecy('simple')->reveal();

        $inited = ValidationRules::initialize($context, [
            fn(GraphQlContext $context) => $simple
        ], [])->toArray();

        self::assertSame($simple, $inited['simple']);
    }

    public function testInitializeWithEmptyFactory()
    {
        $context = $this->prophesize(GraphQlContext::class)->reveal();

        $inited = ValidationRules::initialize($context, [
            fn(GraphQlContext $context) => null
        ], [])->toArray();

        self::assertArrayNotHasKey('simple', $inited);
    }

    public function testInitializeWithVariablesSet()
    {
        $context = $this->prophesize(GraphQlContext::class)->reveal();

        /** @var (ValidationRule&RequiresVariableValues)|ObjectProphecy $prophecy */
        $prophecy = $this->prophesize(ValidationRule::class)->willImplement(RequiresVariableValues::class);
        $prophecy->getName()->willReturn('simple');
        $prophecy->setVariableValues(['my-val'])->shouldBeCalledOnce();
        $simple = $prophecy->reveal();

        $inited = ValidationRules::initialize($context, [
            $simple
        ], ['my-val'])->toArray();

        self::assertSame($simple, $inited['simple']);
    }


}
