<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use Closure;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQlTools\Contract\SchemaRules;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Helper\Resolver\ProxyResolver;


final class Field extends BaseProperties
{
    /** @var InputField[] */
    private array $arguments = [];
    private null|Closure $resolveFunction = null;
    private array $middlewares = [];
    private int $cost = 0;
    private null|Closure $costFunction = null;
    private Closure|bool $visible = true;

    public function visible(bool|Closure $closure): self
    {
        $clone = clone $this;
        $clone->visible = $closure;
        return $clone;
    }

    public function resolvedBy(Closure $closure): self
    {
        $clone = clone $this;
        $clone->resolveFunction = $closure;
        return $clone;
    }

    public function arguments(InputField ...$arguments): self
    {
        return $this->withArguments(...$arguments);
    }

    public function withArguments(InputField ...$arguments): self
    {
        $clone = clone $this;
        $clone->arguments = $arguments;
        return $clone;
    }

    /**
     * Overwrites and sets middlewares to what is given.
     * @param Closure ...$middleware
     * @return $this
     * @api define middlewares to use when resolving this field.
     */
    public function middleware(Closure...$middleware): self
    {
        $clone = clone $this;
        $clone->middlewares = $middleware;
        return $clone;
    }

    /**
     * Append a middleware into the stack of middlewares
     * @param Closure ...$middleware
     * @return $this
     */
    public function appendMiddleware(Closure...$middleware): self
    {
        $clone = clone $this;
        $clone->middlewares = [...$this->middlewares, ...$middleware];
        return $this;
    }

    /**
     * Prepend middlewares to the stack of middlewares.
     * @param array<Closure> $middleware
     * @return $this
     */
    public function prependMiddleware(Closure...$middleware): self
    {
        $clone = clone $this;
        array_unshift($clone->middlewares, ...$middleware);
        return $clone;
    }

    /**
     * The price is what you say a field does cost. You can add a variable component to it
     * passing a second parameter (closure), which can determine a factor to multiply the
     * child complexity. This allows you to determine the MAX cost of a query ahead of time.
     *
     * Example:
     * For pagination, you want to compute the child cost times the max amount of results.
     * `query { field(first: 5) { id } }`
     * The field has a cost of 2 and the id costs one. So you want to multiply it by 5, as
     * 5 children are queried.
     *
     * **The cost will then be**:
     * (amount of children: 5) * (cost of children: 1) + (field cost: 2) = 7
     *
     * **In code this means**:
     * `->cost(2, fn($args): int => $args['first'])`
     *
     * Remember, we are calculating the **MAX** possible cost of the query ahead of time to prevent
     * potentially expensive queries. The actual cost Extension allows you to collect what was
     * actually consumed by aggregating all the costs of the fields. So the max cost might be
     * 7 but as there were only 3 results, the actual cost is (3 * 1) + 2.
     *
     * @param int $price
     * @param Closure(array): (float|int)|null $multiplyChildrenCostBy
     * @return $this
     */
    public function cost(int $price, ?Closure $multiplyChildrenCostBy = null): self
    {
        $clone = clone $this;
        $clone->cost = $price;
        if (!$multiplyChildrenCostBy) {
            $clone->costFunction = static fn(int $childrenComplexity): int => $childrenComplexity + $price;
            return $clone;
        }

        $clone->costFunction = static function (int $childrenComplexity, ?array $args) use ($multiplyChildrenCostBy, $price): int {
            $factor = $multiplyChildrenCostBy($args ?? []);
            return ($factor * $childrenComplexity) + $price;
        };
        return $clone;
    }

    /**
     * @param SchemaRules $schemaRules
     * @return FieldDefinition
     * @throws DefinitionException
     * @internal
     */
    public function toDefinition(SchemaRules $schemaRules): FieldDefinition
    {
        $this->verifyTypeIsSet();
        return new FieldDefinition([
            'name' => $this->name,
            'resolve' => new ProxyResolver($this->resolveFunction ?? null, $this->middlewares),
            'type' => $this->ofType,
            'deprecationReason' => $this->deprecationReason,
            'removalDate' => $this->removalDate,
            'description' => $this->computeDescription(),
            'args' => $this->initArguments($schemaRules),
            'tags' => $this->getTags(),
            'complexity' => $this->costFunction ?? self::freeCost(...),
            'cost' => $this->cost,
            'visible' => $this->visible,
        ]);
    }

    /**
     * @throws DefinitionException
     */
    private function initArguments(SchemaRules $schemaRules): ?array
    {
        $inputFields = [];
        foreach ($this->arguments as $definition) {
            if ($schemaRules->isVisible($definition)) {
                $inputFields[] = $definition->toDefinition();
            }
        }

        return $inputFields;
    }

    private static function freeCost(int $childrenComplexity): int
    {
        return $childrenComplexity;
    }
}