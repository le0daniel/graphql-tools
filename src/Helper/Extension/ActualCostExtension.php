<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Extension;

use Closure;
use GraphQlTools\Events\VisitFieldEvent;

final class ActualCostExtension extends Extension
{
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

    public function jsonSerialize(): mixed
    {
        return $this->usedCost;
    }
}