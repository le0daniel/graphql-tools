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
use GraphQlTools\Helper\Results\GraphQlResult;
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
        $rule->serialize(Argument::type('int'))->willReturn('result-rule');

        $extension = $this->prophesize(ExecutionExtension::class)->willImplement(ProvidesResultExtension::class);
        $extension->isVisibleInResult($context)->willReturn(true);
        $extension->key()->willReturn('extension');
        $extension->serialize(Argument::type('int'))->willReturn('result-extension');

        $result = GraphqlResult::fromExecutionResult(
            new ExecutionResult([
                'test' => true,
            ]),
            new Context(),
            [$rule->reveal()],
            [$extension->reveal()],
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

        $result = GraphqlResult::fromExecutionResult(
            new ExecutionResult(errors: [$error]),
            new Context(),
            [],
            [],
        );

        self::assertEquals([
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

        $result = GraphqlResult::fromExecutionResult(
            new ExecutionResult(errors: [$error]),
            new Context(),
            [],
            [],
        );

        self::assertEquals([
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

        $result = GraphqlResult::fromExecutionResult(
            new ExecutionResult(errors: [$error]),
            new Context(),
            [],
            [],
        );

        self::assertEquals([
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

        $result = GraphqlResult::fromExecutionResult(
            new ExecutionResult(),
            new Context(),
            ['my-rule' => $rule],
            [],
        );
        self::assertSame($rule, $result->getValidationRule('my-rule'));
        self::assertNull($result->getValidationRule('Something'));
    }

    public function testGetExtension()
    {
        $extension = $this->prophesize(ExecutionExtension::class)->reveal();

        $result = GraphqlResult::fromExecutionResult(
            new ExecutionResult(),
            new Context(),
            [],
            [
                'my-extension' => $extension
            ],
        );
        self::assertSame($extension, $result->getExtension('my-extension'));
        self::assertNull($result->getExtension('Something'));
    }
}
