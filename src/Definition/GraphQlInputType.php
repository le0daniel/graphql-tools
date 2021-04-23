<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\InputObjectType;
use GraphQlTools\Definition\Shared\DefinesFields;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Strings;

abstract class GraphQlInputType extends InputObjectType {
    use DefinesFields, HasDescription;

    public function __construct(protected TypeRepository $typeRepository){
        parent::__construct(
            [
                'name' => static::typeName(),
                'description' => $this->description(),
                'fields' => fn() => $this->initInputFields($this->fields()),
            ]
        );
    }

    public static function typeName(): string {
        $typeName = Strings::baseClassName(static::class);
        return str_ends_with($typeName, 'Type')
            ? substr($typeName, 0, -strlen('Type'))
            : $typeName;
    }

}
