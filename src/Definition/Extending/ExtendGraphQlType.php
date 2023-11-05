<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Extending;

use Closure;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Utility\Middleware\Federation;

abstract class ExtendGraphQlType
{

    abstract public function typeName(): string;

    /**
     * Provide an array of middlewares to apply to all fields
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

    /**
     * Return the key for which to use the Federation::key() middleware.
     * @return string|null
     */
    protected function key(): ?string {
        return null;
    }

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