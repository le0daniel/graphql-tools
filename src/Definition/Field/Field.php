<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use Closure;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\Field\Shared\DefinesBaseProperties;
use GraphQlTools\Definition\Field\Shared\DefinesReturnType;
use GraphQlTools\Helper\Middleware;
use GraphQlTools\Helper\ProxyResolver;


final class Field
{
    use DefinesBaseProperties, DefinesReturnType;

    /** @var InputField[] */
    private readonly array $inputFields;

    private null|Closure $resolveFunction = null;
    private array $middlewares = [];

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

    /**
     * @internal
     * @param TypeRegistry $registry
     * @return FieldDefinition
     * @throws DefinitionException
     */
    public function toDefinition(array $excludeTags = []): FieldDefinition
    {
        $resolveFn = empty($this->middlewares)
            ? new ProxyResolver($this->resolveFunction ?? null)
            : new ProxyResolver(Middleware::create($this->middlewares)->then($this->resolveFunction));

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
}