<?php declare(strict_types=1);

namespace GraphQlTools\Data\ValueObjects\Tracing;

use GraphQL\Language\SourceLocation;

class GraphQlErrorLocation
{

    public function __construct(
        public readonly int $line,
        public readonly int $column,
    )
    {
    }

    public static function from(SourceLocation $sourceLocation): self
    {
        return new self(
            $sourceLocation->line,
            $sourceLocation->column,
        );
    }

}