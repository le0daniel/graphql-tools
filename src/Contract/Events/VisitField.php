<?php declare(strict_types=1);

namespace GraphQlTools\Contract\Events;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * @property-read int $eventTimeInNanoSeconds
 * @property-read mixed $typeData
 * @property-read array $arguments
 * @property-read ResolveInfo $info
 */
interface VisitField
{

    public function stopImmediatePropagation(): void;

    public function then(Closure $afterResolution): void;

    public function canDefer(): bool;

    public function defer(?string $label = null): void;

}