<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Extending;

use Closure;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Utility\Middleware\Federation;

abstract class ExtendGraphQlType
{
    abstract protected function key(): ?string;

    abstract public function typeName(): string;

    /**
     * @return array<Closure>
     */
    protected function middleware(): array
    {
        return [];
    }

    /**
     * @param TypeRegistry $registry
     * @return array<Field>
     */
    abstract protected function fields(TypeRegistry $registry): array;

    final public function getFields(TypeRegistry $registry): array
    {
        $middleware = $this->middleware();
        if ($this->key()) {
            array_unshift($middleware, Federation::key($this->key()));
        }

        if (empty($middleware)) {
            return $this->fields($registry);
        }

        $fields = [];
        foreach ($this->fields($registry) as $field) {
            $fields[] = $field->prependMiddleware(...$middleware);
        }

        return $fields;
    }
}