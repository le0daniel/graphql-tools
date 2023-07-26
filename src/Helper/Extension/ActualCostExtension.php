<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Extension;

use Closure;
use GraphQlTools\Events\StartEvent;
use GraphQlTools\Events\VisitFieldEvent;

final class ActualCostExtension extends Extension
{
    private const DEFAULT_MIN_QUERY_COST = 2;

    public function __construct(
        private readonly int $minQueryCost = self::DEFAULT_MIN_QUERY_COST,
    )
    {
    }

    private int $usedCost = 0;

    public function key(): string
    {
        return 'actualCost';
    }

    public function visitField(VisitFieldEvent $event): ?Closure
    {
        $this->usedCost += $event->info->fieldDefinition->config['cost'] ?? 0;
        return null;
    }

    public function getCost(): int {
        return max($this->usedCost, $this->minQueryCost);
    }

    public function jsonSerialize(): int
    {
        return $this->getCost();
    }
}