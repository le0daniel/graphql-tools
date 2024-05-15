<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Results;

use GraphQlTools\Helper\OperationContext;
use RuntimeException;

final readonly class CompleteResult extends Result
{
    protected function appendToResult(array $result): array
    {
        return $result;
    }

    public static function from(
        mixed $data,
        array $errors,
        OperationContext $context
    ): self {
        return new self(
            $data,
            $errors,
            $context->context,
            $context->validationRules,
            $context->extensions,
        );
    }

    public static function withErrorsOnly(
        array            $errors,
        OperationContext $context
    ): self
    {
        if (count($errors) < 1) {
            throw new RuntimeException("Expected at least one error, got: " . count($errors));
        }

        return new self(
            null,
            $errors,
            $context->context,
            $context->validationRules,
            $context->extensions,
        );
    }
}