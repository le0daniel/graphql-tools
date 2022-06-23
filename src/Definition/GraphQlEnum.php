<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\EnumType;
use GraphQlTools\Data\Enums\MyEnum;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\Classes;
use ReflectionEnum;

abstract class GraphQlEnum extends EnumType
{
    private const CLASS_POSTFIX = 'Enum';
    use HasDescription;

    final public function __construct()
    {
        parent::__construct(
            [
                'name' => static::typeName(),
                'description' => $this->description(),
                'values' => $this->initValues(),
            ]
        );
    }

    /**
     * @return array<string, mixed>
     * @throws DefinitionException
     */
    private function initValues(): array {
        $valuesOrEnumClassName = $this->values();
        if (is_array($valuesOrEnumClassName)) {
            return $valuesOrEnumClassName;
        }

        if (!enum_exists($valuesOrEnumClassName)) {
            throw DefinitionException::from($valuesOrEnumClassName, 'array<key: string, value: mixed>', 'enum');
        }

        return Arrays::mapWithKeys(
            $valuesOrEnumClassName::cases(),
            fn($index, $enum): array => [$enum->name, $enum]
        );
    }

    /**
     * Return a key value array or a serial array containing
     * either the key and the internal value or the keys only.
     *
     * @return array<string,mixed>|class-string
     */
    abstract protected function values(): array|string;

    public static function typeName(): string
    {
        $typeName = Classes::baseName(static::class);
        return str_ends_with($typeName, self::CLASS_POSTFIX)
            ? substr($typeName, 0, -strlen(self::CLASS_POSTFIX))
            : $typeName;
    }

}
