<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\InterfaceType;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Definition\Shared\DefinesFields;
use GraphQlTools\Definition\Shared\ResolvesType;
use GraphQlTools\Helper\TypeRegistry;
use GraphQlTools\Utility\Classes;

abstract class GraphQlInterface extends InterfaceType {
    use DefinesFields, HasDescription, ResolvesType;
    private const CLASS_POSTFIX = 'Interface';

    /**
     * Return an array of fields of that specific type. The fields
     * are then initialized correctly and a proxy attached to them.
     *
     * @return Field[]
     */
    abstract protected function fields(): array;

    final public function __construct(protected readonly TypeRegistry $typeRegistry) {
        parent::__construct(
            [
                'name' => static::typeName(),
                'description' => $this->description(),
                'fields' => fn() => $this->initFields($this->fields(), true),
            ]
        );
    }

    public static function typeName(): string {
        $typeName = Classes::baseName(static::class);
        return str_ends_with($typeName, self::CLASS_POSTFIX)
            ? substr($typeName, 0, -strlen(self::CLASS_POSTFIX))
            : $typeName;
    }

}
