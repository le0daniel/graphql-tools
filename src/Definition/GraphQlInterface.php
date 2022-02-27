<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\InterfaceType;
use GraphQlTools\Definition\Field\GraphQlField;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Definition\Shared\DefinesFields;
use GraphQlTools\Definition\Shared\ResolvesType;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Classes;

abstract class GraphQlInterface extends InterfaceType {
    use DefinesFields, HasDescription, ResolvesType;

    private const CLASS_POSTFIX = 'Interface';

    /**
     * Return an array of fields of that specific type. The fields
     * are then initialized correctly and a proxy attached to them.
     *
     * @return GraphQlField[]
     */
    abstract protected function fields(): array;

    public function __construct(private TypeRepository $typeRepository) {
        parent::__construct(
            [
                'name' => static::typeName(),
                'description' => $this->description(),
                'fields' => fn() => $this->initFields($this->fields()),
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
