<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\InterfaceType;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Definition\Shared\DefinesFields;
use GraphQlTools\Definition\Shared\ResolvesType;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Classes;
use GraphQlTools\Utility\Strings;

abstract class GraphQlInterface extends InterfaceType {
    private const CLASS_POSTFIX = 'Interface';
    use DefinesFields, HasDescription, ResolvesType;

    public function __construct(protected TypeRepository $typeRepository) {
        parent::__construct(
            [
                'name' => static::typeName(),
                'description' => $this->description(),
                'fields' => fn() => $this->initFields(),
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
