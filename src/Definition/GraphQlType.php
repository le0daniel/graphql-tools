<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\ObjectType;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Definition\Shared\DefinesFields;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Classes;

abstract class GraphQlType extends ObjectType {
    use DefinesFields, HasDescription;
    private const CLASS_POSTFIX = 'Type';

    public function __construct(
        protected TypeRepository $typeRepository
    ){
        parent::__construct(
            [
                'name' => static::typeName(),
                'description' => $this->description(),
                'fields' => fn() => $this->initFields(),
                'interfaces' => fn() => array_map([$this, 'declarationToType'], $this->interfaces()),
                GraphQlField::METADATA_CONFIG_KEY => $this->metadata(),
            ]
        );
    }

    protected function metadata(): mixed {
        return null;
    }

    /**
     * Array returning the Interface types resolved by the TypeRepository.
     * @return array
     */
    protected function interfaces(): array{
        return [];
    }

    public static function typeName(): string {
        $typeName = Classes::baseName(static::class);
        return str_ends_with($typeName, self::CLASS_POSTFIX)
            ? substr($typeName, 0, -strlen(self::CLASS_POSTFIX))
            : $typeName;
    }

}
