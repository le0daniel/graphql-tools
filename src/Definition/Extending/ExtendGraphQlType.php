<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Extending;

use Closure;
use GraphQlTools\Contract\ExtendType;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Utility\Middleware\Federation;
use GraphQlTools\Utility\Types;

/**
 * Naming pattern Extends[TypeOrInterfaceName][Type|Interface]
 */
abstract class ExtendGraphQlType implements ExtendType
{
    /**
     * Return type name of class name
     * @return string
     */
    public function typeName(): string {
        return Types::inferExtensionName(static::class);
    }

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
        $middleware = $this->key()
            ? [Federation::key($this->key()), ...$this->middleware()]
            : $this->middleware();

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