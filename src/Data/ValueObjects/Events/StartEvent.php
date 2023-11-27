<?php declare(strict_types=1);

namespace GraphQlTools\Data\ValueObjects\Events;

use GraphQL\Language\AST\DocumentNode;
use GraphQlTools\Data\ValueObjects\Events\Event;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Utility\Query;

final class StartEvent extends Event
{

    public function __construct(
        public readonly string|DocumentNode $query,
        public readonly GraphQlContext      $context,
        public readonly null|string         $operationName,
    )
    {
        parent::__construct();
    }

    public function isIntrospectionQuery(): bool
    {
        return Query::isIntrospection($this->query);
    }

}