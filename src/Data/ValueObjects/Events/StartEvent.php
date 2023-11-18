<?php declare(strict_types=1);

namespace GraphQlTools\Data\ValueObjects\Events;

use GraphQlTools\Data\ValueObjects\Events\Event;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Utility\Query;

/**
 * @method static create(string $query, GraphQlContext $context, string|null $operationName)
 */
final class StartEvent extends Event
{

    protected function __construct(
        public readonly string         $query,
        public readonly GraphQlContext $context,
        public readonly null|string    $operationName,
    )
    {
    }

    public function isIntrospectionQuery(): bool
    {
        return Query::isIntrospection($this->query);
    }

}