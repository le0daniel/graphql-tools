<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use Closure;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Shared\DefinesArguments;
use GraphQlTools\Definition\Field\Shared\DefinesField;
use GraphQlTools\Definition\Field\Shared\DefinesMetadata;
use GraphQlTools\Definition\Field\Shared\DefinesReturnType;
use GraphQlTools\Definition\Field\Shared\Deprecatable;
use GraphQlTools\Helper\Middleware;
use GraphQlTools\Helper\ProxyResolver;


class Field implements DefinesGraphQlType
{
    use DefinesField, Deprecatable, DefinesReturnType, DefinesArguments, DefinesMetadata;
    private null|Closure $resolveFunction = null;
    private array $middlewares = [];

    final protected function __construct(public readonly string $name)
    {
    }

    final public static function withName(string $name): static
    {
        return new self($name);
    }

    public function middleware(Closure... $middleware): self {
        $this->middlewares = $middleware;
        return $this;
    }

    final public function toDefinition(TypeRegistry $registry): FieldDefinition
    {
        $resolveFn = empty($this->middlewares)
            ? new ProxyResolver($this->resolveFunction ?? null)
            : new ProxyResolver(Middleware::create($this->middlewares)->then($this->resolveFunction));

        $this->verifyTypeIsSet();
        return FieldDefinition::create([
            'name' => $this->name,
            'resolve' => $resolveFn,
            'type' => $this->resolveReturnType($registry),
            'deprecationReason' => $this->deprecationReason,
            'removalDate' => $this->removalDate,
            'description' => $this->addDeprecationToDescription($this->description ?? ''),
            'args' => $this->buildArguments($registry),
            '__metadata' => $this->metadata
        ]);
    }

    public function resolvedBy(Closure $closure): self
    {
        $this->resolveFunction = $closure;
        return $this;
    }
}