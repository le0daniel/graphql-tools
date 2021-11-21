<?php declare(strict_types=1);

namespace GraphQlTools\Events;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\Event;

final class FieldResolutionEvent extends Event
{

    public function __construct(int $eventTimeInNanoSeconds, public mixed $typeData, public array $arguments, public ResolveInfo $info)
    {
        parent::__construct($eventTimeInNanoSeconds);
    }

}