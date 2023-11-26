<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Extension;

use GraphQL\Error\DebugFlag;
use GraphQlTools\Contract\ExecutionExtension;
use GraphQlTools\Contract\Extension\InteractsWithFieldResolution;
use GraphQlTools\Contract\ProvidesResultExtension;
use GraphQlTools\Data\ValueObjects\Events\VisitFieldEvent;

final class ActualCostExtension implements ProvidesResultExtension, ExecutionExtension, InteractsWithFieldResolution
{
    private const DEFAULT_MIN_QUERY_COST = 2;

    private int $usedCost = 0;

    public function __construct(
        private readonly int $minQueryCost = self::DEFAULT_MIN_QUERY_COST,
    )
    {
    }

    public function key(): string
    {
        return 'actualCost';
    }

    public function visitField(VisitFieldEvent $event): void
    {
        $this->usedCost += $event->info->fieldDefinition->config['cost'] ?? 0;
    }

    public function getCost(): int {
        return max($this->usedCost, $this->minQueryCost);
    }

    public function isVisibleInResult($context): bool
    {
        return true;
    }

    public function serialize(int $debug = DebugFlag::NONE): int
    {
        return $this->getCost();
    }

    public function priority(): int
    {
        return 100;
    }

    public function getName(): string
    {
        return ActualCostExtension::class;
    }

    public function isEnabled(): bool
    {
        return true;
    }
}