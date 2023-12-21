<?php declare(strict_types=1);

namespace GraphQlTools\Data\ValueObjects\Events;

use Closure;
use GraphQL\Executor\Values;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\Events\VisitField as VisitFieldContract;

/**
 * @internal
 */
final class VisitFieldEvent extends Event implements VisitFieldContract
{
    private bool $isDeferred = false;
    private ?string $deferLabel = null;
    private bool $stopImmediatePropagation = false;
    private array $afterResolution = [];

    public function __construct(
        public readonly mixed       $typeData,
        public readonly array       $arguments,
        public readonly ResolveInfo $info,
        private readonly bool       $canDefer,
        public readonly array       $directiveNames,
    )
    {
        parent::__construct();
    }

    public function stopImmediatePropagation(): void
    {
        $this->stopImmediatePropagation = true;
    }

    /**
     * @return bool
     * @internal
     */
    public function isStopped(): bool
    {
        return $this->stopImmediatePropagation;
    }

    /**
     * Add a hook after the field (and all promises) have been resolved to a value.
     * @param Closure(mixed $value):void $afterResolution
     * @return void
     */
    public function then(Closure $afterResolution): void
    {
        $this->afterResolution[] = $afterResolution;
    }

    /**
     * @param mixed $value
     * @return mixed
     * @internal
     */
    public function resolveValue(mixed $value): mixed
    {
        foreach ($this->afterResolution as $closure) {
            $closure($value);
        }
        return $value;
    }

    /**
     * @return bool
     * @internal
     */
    public function isDeferred(): bool
    {
        return !!$this->isDeferred;
    }

    public function canDefer(): bool
    {
        return $this->canDefer;
    }

    /**
     * @return string|null
     * @internal
     */
    public function getDeferLabel(): ?string
    {
        return $this->deferLabel;
    }

    public function defer(?string $label = null): void
    {
        if ($this->canDefer()) {
            $this->stopImmediatePropagation = true;
            $this->isDeferred = true;
            $this->deferLabel = $label;
        }
    }

    public function hasDirective(string $name): bool
    {
        return in_array($name, $this->directiveNames, true);
    }

    public function getDirectiveArguments(string $name): array
    {
        return Values::getDirectiveValues(
            $this->info->schema->getDirective($name),
            $this->info->fieldNodes[0],
            $this->info->variableValues,
        );
    }
}