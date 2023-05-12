<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Extending;

use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Utility\Middleware\Federation;

abstract class ExtendGraphQlType
{
    abstract protected function key(): ?string;

    abstract public function typeName(): string;

    /**
     * @param TypeRegistry $registry
     * @return array<Field>
     */
    abstract protected function fields(TypeRegistry $registry): array;

    final public function getFields(TypeRegistry $registry): array {
        if (!$this->key()) {
            return $this->fields($registry);
        }

        $middleware = Federation::key($this->key());

        $fields = [];
        foreach ($this->fields($registry) as $field) {
            $fields[] = $field->prependMiddleware($middleware);
        }

        return $fields;
    }
}