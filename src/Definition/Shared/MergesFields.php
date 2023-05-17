<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

use Closure;

trait MergesFields
{
    private array $mergedFieldFactories = [];

    final public function mergeFieldFactories(Closure ... $factories): static {
        $instance = clone $this;
        $instance->mergedFieldFactories = [
            ...$this->mergedFieldFactories,
            ...$factories
        ];
        return $instance;
    }

}