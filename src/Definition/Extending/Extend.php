<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Extending;

use Closure;
use GraphQlTools\Contract\ExtendType;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;

final class Extend implements ExtendType
{
    private readonly Closure $fields;
    private array $middleware = [];

    private function __construct(private readonly string $typeName)
    {
    }

    public static function type(string $name): self
    {
        return new self($name);
    }

    public static function interface(string $name): self
    {
        return new self($name);
    }

    public function withFields(Closure $fields): self
    {
        $clone = clone $this;
        $clone->fields = $fields;
        return $clone;
    }

    public function applyMiddleware(Closure...$closure): self
    {
        $clone = clone $this;
        $clone->middleware = $closure;
        return $clone;
    }

    /**
     * @return string
     * @internal
     */
    public function typeName(): string
    {
        return $this->typeName;
    }

    /**
     * @param TypeRegistry $registry
     * @return array<Field>
     * @internal
     */
    public function getFields(TypeRegistry $registry): array
    {
        $fields = ($this->fields)($registry);

        if (empty($this->middleware)) {
            return $fields;
        }

        return array_map(fn(Field $field): Field => $field->prependMiddleware(...$this->middleware), $fields);
    }
}