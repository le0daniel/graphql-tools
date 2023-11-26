<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper\Results;

use GraphQL\Error\ClientAware;
use GraphQL\Error\Error;
use GraphQL\Error\ProvidesExtensions;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Validator\Rules\ValidationRule;
use GraphQlTools\Contract\ExecutionExtension;
use GraphQlTools\Contract\ProvidesResultExtension;
use GraphQlTools\Helper\Context;
use GraphQlTools\Helper\Extensions;
use GraphQlTools\Helper\Results\CompleteResult;
use GraphQlTools\Helper\ValidationRules;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class GraphQlResultTest extends TestCase
{
    use ProphecyTrait;

    public function testToArray()
    {
        $context = new Context();
        /** @var ObjectProphecy|ValidationRule&ProvidesResultExtension $rule */
        $rule = $this->prophesize(ValidationRule::class)
            ->willImplement(ProvidesResultExtension::class);
        $rule->isVisibleInResult($context)->willReturn(true);
        $rule->key()->willReturn('rule');
        $rule->getName()->willReturn('some-rule');
        $rule->serialize(Argument::type('int'))->willReturn('result-rule');

        $extension = $this->prophesize(ExecutionExtension::class)->willImplement(ProvidesResultExtension::class);
        $extension->isVisibleInResult($context)->willReturn(true);
        $extension->key()->willReturn('extension');
        $extension->serialize(Argument::type('int'))->willReturn('result-extension');
        $extension->getName()->willReturn('extension');

        $result = new CompleteResult(
            [
                'test' => true,
            ],
            [],
            new Context(),
            ValidationRules::initialize(new Context(), [$rule->reveal()], null),
            new Extensions($extension->reveal()),
        );

        self::assertEquals([
            'data' => ['test' => true],
            'extensions' => [
                "rule" => 'result-rule',
                "extension" => 'result-extension',
            ]
        ], $result->toArray());
    }

    private function getThrowableMock(): ObjectProphecy|(\Throwable&ClientAware&ProvidesExtensions)
    {
        return $this->prophesize(\Throwable::class)
            ->willImplement(ClientAware::class)
            ->willImplement(ProvidesExtensions::class);
    }

    public function testToArrayErrorFormatter(): void {
        $exception = $this->getThrowableMock();
        $exception->isClientSafe()->willReturn(true);
        $exception->getExtensions()->willReturn(['code' => 404]);

        $error = new Error(previous: $exception->reveal());

        $result = new CompleteResult(
            null,
            [$error],
            new Context(),
            new ValidationRules(),
            new Extensions(),
        );

        self::assertEquals([
            'data' => null,
            'errors' => [
                [
                    'message' => '',
                    'extensions' => [
                        'code' => 404
                    ]
                ]
            ]
        ], $result->toArray());
    }

    public function testFormatWhenNotClientAware(): void {
        $exception = $this->getThrowableMock();
        $exception->isClientSafe()->willReturn(false);
        $exception->getExtensions()->willReturn(null);
        $error = new Error(previous: $exception->reveal());

        $result = new CompleteResult(
            null,
            [$error],
            new Context(),
            new ValidationRules(),
            new Extensions(),
        );

        self::assertEquals([
            'data' => null,
            'errors' => [
                [
                    'message' => 'Internal server error',
                ]
            ]
        ], $result->toArray());
    }

    public function testFormatWithEmptyExceptions(): void {
        $exception = $this->getThrowableMock();
        $exception->isClientSafe()->willReturn(true);
        $error = new Error(previous: $exception->reveal());
        $exception->getExtensions()->willReturn(null);

        $result = new CompleteResult(
            null,
            [$error],
            new Context(),
            new ValidationRules(),
            new Extensions(),
        );

        self::assertEquals([
            'data' => null,
            'errors' => [
                [
                    'message' => '',
                ]
            ]
        ], $result->toArray());
    }

    public function testGetValidationRule()
    {
        $rule = $this->prophesize(ValidationRule::class)->reveal();

        $result = new CompleteResult(
            null,
            [],
            new Context(),
            new ValidationRules(['my-rule' => $rule]),
            new Extensions(),
        );
        self::assertSame($rule, $result->getValidationRule('my-rule'));
        self::assertNull($result->getValidationRule('Something'));
    }

    public function testGetExtension()
    {
        $extensionProhecy = $this->prophesize(ExecutionExtension::class);
        $extensionProhecy->getName()->willReturn('my-extension');
        $extension = $extensionProhecy->reveal();

        $result = new CompleteResult(
            null,
            [],
            new Context(),
            new ValidationRules(),
            new Extensions($extension),
        );
        self::assertSame($extension, $result->getExtension('my-extension'));
        self::assertNull($result->getExtension('Something'));
    }
}
