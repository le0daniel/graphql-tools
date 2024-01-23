<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Extending;

use Closure;
use GraphQlTools\Contract\ExtendsGraphQlDefinition;

final class ExtendInterface implements ExtendsGraphQlDefinition
{
    private ?Closure $resolver;

    public function __construct(public readonly string $interfaceName)
    {
    }

    public function typeName(): string
    {
        return $this->interfaceName;
    }

    public function getResolver(): ?Closure
    {
        return $this->resolver ?? null;
    }

    public function withResolver(Closure $resolver): self {
        $clone = clone $this;
        $clone->resolver = $resolver;
        return $clone;
    }

}