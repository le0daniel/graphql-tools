<?php declare(strict_types=1);

namespace GraphQlTools\Data\ValueObjects\Events;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\Events\VisitField as VisitFieldContract;

/**
 * @internal
 * @method static create(mixed $typeData, array $arguments, ResolveInfo $resolveInfo, bool $canDefer): static
 */
final class VisitFieldEvent extends Event implements VisitFieldContract
{
    private bool $isDeferred = false;
    private ?string $deferLabel = null;
    private bool $stopImmediatePropagation = false;
    private array $afterResolution = [];

    protected function __construct(
        public readonly mixed       $typeData,
        public readonly array       $arguments,
        public readonly ResolveInfo $info,
        private readonly bool       $canDefer,
    )
    {
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
     * Add a hook after the field has been resolved.
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

    /**
     * Propose to the Executor to defer execution of this field.
     * The executor will make the final decision if it is allowed to be skipped or not.
     * @param string|null $label
     * @return void
     */
    public function defer(?string $label = null): void
    {
        if ($this->canDefer()) {
            $this->stopImmediatePropagation = true;
            $this->isDeferred = true;
            $this->deferLabel = $label;
        }
    }

}