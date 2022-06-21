<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\InputObjectType;
use GraphQlTools\Definition\Shared\DefinesFields;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Helper\TypeRegistry;
use GraphQlTools\Utility\Classes;

abstract class GraphQlInputType extends InputObjectType
{
    use DefinesFields, HasDescription;

    private const CLASS_POSTFIX = 'Type';

    final public function __construct(protected readonly TypeRegistry $typeRegistry)
    {
        parent::__construct(
            [
                'name' => static::typeName(),
                'description' => $this->description(),
                'fields' => fn() => $this->initInputFields($this->fields()),
            ]
        );
    }

    abstract protected function fields(): array;

    public static function typeName(): string
    {
        $typeName = Classes::baseName(static::class);
        return str_ends_with($typeName, self::CLASS_POSTFIX)
            ? substr($typeName, 0, -strlen(self::CLASS_POSTFIX))
            : $typeName;
    }

}
