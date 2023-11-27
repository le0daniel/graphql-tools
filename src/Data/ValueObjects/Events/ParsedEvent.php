<?php declare(strict_types=1);

namespace GraphQlTools\Data\ValueObjects\Events;

use GraphQL\Language\AST\DocumentNode;

final class ParsedEvent extends Event
{
    public function __construct(
        public readonly DocumentNode $source,
        public readonly null|string  $operationName,
    )
    {
        parent::__construct();
    }
}