<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\ObjectType;
use GraphQlTools\Definition\Field\GraphQlField;
use GraphQlTools\Definition\Shared\DefinesTypes;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Definition\Shared\DefinesFields;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Classes;
use GraphQlTools\Utility\Fields;
use RuntimeException;

abstract class GraphQlType extends ObjectType {
    use DefinesFields, HasDescription, DefinesTypes;
    private const CLASS_POSTFIX = 'Type';

    /**
     * Return an array of fields of that specific type. The fields
     * are then initialized correctly and a proxy attached to them.
     *
     * @return GraphQlField[]
     */
    abstract protected function fields(): array;

    public function __construct(
        private TypeRepository $typeRepository
    ){
        parent::__construct(
            [
                'name' => static::typeName(),
                'description' => $this->description(),
                'fields' => fn() => $this->initFields($this->fields()),
                'interfaces' => fn() => $this->initTypes($this->interfaces()),
                Fields::METADATA_CONFIG_KEY => $this->metadata(),
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
