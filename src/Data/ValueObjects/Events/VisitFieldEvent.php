<?php declare(strict_types=1);

namespace GraphQlTools\Data\ValueObjects\Events;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * @method static create(mixed $typeData, array $arguments, ResolveInfo $resolveInfo, bool $hasBeenDeferred): static
 */
final class VisitFieldEvent extends Event
{
    private bool|string $shouldDefer = false;
    private bool $stopImmediatePropagation = false;
    private array $afterResolution = [];

    protected function __construct(
        public readonly mixed       $typeData,
        public readonly array       $arguments,
        public readonly ResolveInfo $info,
        public readonly bool $hasBeenDeferred,
    )
    {
    }

    public function stopImmediatePropagation(): void
    {
        $this->stopImmediatePropagation = true;
    }

    /**
     * @internal
     * @return bool
     */
    public function isStopped(): bool {
        return $this->stopImmediatePropagation;
    }

    /**
     * Add a hook after the field has been resolved.
     * @param Closure(mixed $value):void $afterResolution
     * @return void
     */
    public function then(Closure $afterResolution): void {
        $this->afterResolution[] = $afterResolution;
    }

    /**
     * @internal
     * @param mixed $value
     * @return mixed
     */
    public function resolveValue(mixed $value): mixed {
        foreach ($this->afterResolution as $closure) {
            $closure($value);
        }
        return $value;
    }

    /**
     * @internal
     * @return bool
     */
    public function shouldDefer(): bool {
        return !!$this->shouldDefer;
    }

    /**
     * @internal
     * @return string|null
     */
    public function getDeferLabel(): ?string {
        return is_string($this->shouldDefer) ? $this->shouldDefer : null;
    }

    /**
     * Stops event propagation and defers the execution of this field.
     * @param string|null $label
     * @return void
     */
    public function defer(?string $label = null): void {
        $this->stopImmediatePropagation = true;
        $this->shouldDefer = $label ?? true;
    }

}