<?php declare(strict_types=1);

namespace GraphQlTools\Events;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\Event;

/**
 * @method static create(mixed $typeData, array $arguments, ResolveInfo $resolveInfo)
 */
final class VisitFieldEvent extends Event
{

    protected function __construct(
        public readonly mixed       $typeData,
        public readonly array       $arguments,
        public readonly ResolveInfo $info
    )
    {
    }

}