<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use BackedEnum;
use GraphQL\Type\Definition\EnumType;
use GraphQlTools\Definition\Shared\CanBeDeprecated;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Test\Dummies\Enum\Eating;
use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\Classes;
use ReflectionEnum;

abstract class GraphQlEnum
{
    private const CLASS_POSTFIX = 'Enum';
    use HasDescription, CanBeDeprecated;

    public function toDefinition(): EnumType {
        return new EnumType([
            'name' => static::typeName(),
            'description' => $this->addDeprecationToDescription($this->description()),
            'values' => $this->initValues(),
            'deprecationReason' => $this->deprecationReason,
            'removalDate' => $this->removalDate,
        ]);
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
     * @return array<string,mixed>|class-string<BackedEnum>
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
