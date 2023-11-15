<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Extending;

use Closure;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Utility\Middleware\Federation;
use function _PHPStan_c6b09fbdf\RingCentral\Psr7\str;

/**
 * Naming pattern Extends[TypeOrInterfaceName][Type|Interface]
 */
abstract class ExtendGraphQlType
{

    /**
     * Return type name of class name
     * @return string
     */
    public function typeName(): string {
        $parts = explode('\\', static::class);
        $baseName = end($parts);

        if (str_starts_with($baseName, 'Extends')) {
            $baseName = substr($baseName, strlen('Extends'));
        }

        return match (true) {
            str_ends_with($baseName, 'Type') => substr($baseName, 0, -4),
            str_ends_with($baseName, 'Interface') => substr($baseName, 0, -9),
            default => $baseName
        };
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