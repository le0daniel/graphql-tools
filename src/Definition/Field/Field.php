<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use Closure;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\Field\Shared\DefinesArguments;
use GraphQlTools\Definition\Field\Shared\DefinesField;
use GraphQlTools\Definition\Field\Shared\DefinesReturnType;
use GraphQlTools\Definition\Field\Shared\DefinesTags;
use GraphQlTools\Definition\Field\Shared\Deprecatable;
use GraphQlTools\Helper\Middleware;
use GraphQlTools\Helper\ProxyResolver;


class Field implements DefinesGraphQlType
{
    use DefinesField, Deprecatable, DefinesReturnType, DefinesArguments, DefinesTags;
    private null|Closure $resolveFunction = null;
    private array $middlewares = [];

    final protected function __construct(public readonly string $name)
    {
    }

    final public static function withName(string $name): static
    {
        return new self($name);
    }

    /**
     * @api define middlewares to use when resolving this field.
     * @param Closure ...$middleware
     * @return $this
     */
    public function middleware(Closure... $middleware): self {
        $this->middlewares = $middleware;
        return $this;
    }

    /**
     * @internal
     * @param TypeRegistry $registry
     * @return FieldDefinition
     * @throws DefinitionException
     */
    final public function toDefinition(TypeRegistry $registry): FieldDefinition
    {
        $resolveFn = empty($this->middlewares)
            ? new ProxyResolver($this->resolveFunction ?? null)
            : new ProxyResolver(Middleware::create($this->middlewares)->then($this->resolveFunction));

        $this->verifyTypeIsSet();
        return new FieldDefinition([
            'name' => $this->name,
            'resolve' => $resolveFn,
            'type' => $this->resolveReturnType($registry),
            'deprecationReason' => $this->deprecationReason,
            'removalDate' => $this->removalDate,
            'description' => $this->addDeprecationToDescription($this->description ?? ''),
            'args' => $this->buildArguments($registry),
            'tags' => $this->getTags(),
        ]);
    }

    public function resolvedBy(Closure $closure): self
    {
        $this->resolveFunction = $closure;
        return $this;
    }
}