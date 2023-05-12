<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use Closure;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\Field\Shared\DefinesBaseProperties;
use GraphQlTools\Definition\Field\Shared\DefinesReturnType;
use GraphQlTools\Helper\Resolver\MiddlewareResolver;
use GraphQlTools\Helper\Resolver\ProxyResolver;


final class Field
{
    use DefinesBaseProperties, DefinesReturnType;

    /** @var InputField[] */
    private readonly array $inputFields;

    private null|Closure $resolveFunction = null;
    private array $middlewares = [];
    private null|Closure $costFunction = null;

    protected function __construct(public readonly string $name)
    {
    }

    public static function withName(string $name): static
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

    public function cost(int|Closure $priceOrFunction): self {
        if ($priceOrFunction instanceof Closure) {
            $this->costFunction = $priceOrFunction;
            return $this;
        }

        $this->costFunction = static fn(int $childrenComplexity): int => $childrenComplexity + $priceOrFunction;
        return $this;
    }

    /**
     * Clones and adds a middleware at the beginning. Ensures the state is not mutated from the outside if reused.
     * @param array<Closure> $middleware
     * @return $this
     */
    public function prependMiddleware(...$middleware): self {
        $instance = clone $this;
        array_unshift($instance->middlewares, ...$middleware);
        return $instance;
    }

    /**
     * @param array $excludeTags
     * @return FieldDefinition
     * @throws DefinitionException
     * @internal
     */
    public function toDefinition(array $excludeTags = []): FieldDefinition
    {
        $resolveFn = empty($this->middlewares)
            ? new ProxyResolver($this->resolveFunction ?? null)
            : new MiddlewareResolver($this->resolveFunction, $this->middlewares);

        $this->verifyTypeIsSet();
        return new FieldDefinition([
            'name' => $this->name,
            'resolve' => $resolveFn,
            'type' => $this->ofType,
            'deprecationReason' => $this->deprecationReason,
            'removalDate' => $this->removalDate,
            'description' => $this->computeDescription(),
            'args' => $this->initArguments($excludeTags),
            'tags' => $this->getTags(),
            'complexity' => $this->costFunction ?? self::freeCost(...),
        ]);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function resolvedBy(Closure $closure): self
    {
        $this->resolveFunction = $closure;
        return $this;
    }

    final public function withArguments(InputField ...$arguments): self
    {
        $this->inputFields = $arguments;
        return $this;
    }

    final protected function initArguments(array $excludeTags = []): ?array
    {
        if (!isset($this->inputFields)) {
            return null;
        }

        $hasTagsToExclude = !empty($excludeTags);
        $inputFields = [];
        foreach ($this->inputFields as $definition) {
            if ($hasTagsToExclude && $definition->containsAnyOfTags(...$excludeTags)) {
                continue;
            }

            $inputFields[] = $definition->toDefinition();
        }

        return $inputFields;
    }

    private static function freeCost(int $childrenComplexity): int {
        return $childrenComplexity;
    }
}