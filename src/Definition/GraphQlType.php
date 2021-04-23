<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\ObjectType;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Definition\Shared\DefinesFields;
use GraphQlTools\Resolver\ProxyResolver;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Strings;

abstract class GraphQlType extends ObjectType {
    use DefinesFields, HasDescription;

    /**
     * Use this config key on a field to declare the field as
     * beta. This will add a message to the response.
     */
    public const BETA_FIELD_CONFIG_KEY = 'isBeta';

    public function __construct(
        protected TypeRepository $typeRepository
    ){
        parent::__construct(
            [
                'name' => static::typeName(),
                'description' => $this->description(),
                'fields' => fn() => $this->initFields(),
                'resolveField' => [ProxyResolver::class, 'default'],
                'interfaces' => array_map([$this, 'resolveFieldType'],$this->interfaces()),
            ]
        );
    }

    /**
     * Array returning the Interface types resolved by the TypeRepository.
     * @return array
     */
    protected function interfaces(): array{
        return [];
    }

    public static function typeName(): string {
        $typeName = Strings::baseClassName(static::class);
        return str_ends_with($typeName, 'Type')
            ? substr($typeName, 0, -strlen('Type'))
            : $typeName;
    }

}
