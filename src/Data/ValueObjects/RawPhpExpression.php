<?php declare(strict_types=1);

namespace GraphQlTools\Data\ValueObjects;

use Stringable;

final class RawPhpExpression implements Stringable
{
    public function __construct(
        private readonly string|RawPhpExpression $expression
    )
    {
    }

    public function toString(): string {
        return (string) $this->expression;
    }

    public function __toString()
    {
        return $this->toString();
    }
}