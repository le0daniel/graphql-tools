<?php declare(strict_types=1);

namespace GraphQlTools\Data\ValueObjects;

use Stringable;

final class RawPhpExpression implements Stringable
{
    private readonly string $expression;

    public function __construct(string|RawPhpExpression $expression)
    {
        $this->expression = $expression instanceof RawPhpExpression
            ? $expression->toString()
            : $expression;
    }

    public function toString(): string {
        return $this->expression;
    }

    public function __toString()
    {
        return $this->toString();
    }
}