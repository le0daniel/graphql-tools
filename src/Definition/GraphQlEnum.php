<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\EnumType;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\SchemaRules;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\EnumValue;
use GraphQlTools\Definition\Shared\HasDeprecation;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\Types;
use UnitEnum;

abstract class GraphQlEnum implements DefinesGraphQlType
{
    use HasDescription, HasDeprecation;

    public function toDefinition(TypeRegistry $registry, SchemaRules $schemaRules): EnumType
    {
        return new EnumType([
            'name' => $this->getName(),
            'description' => $this->addDeprecationToDescription($this->description()),
            'values' => fn() => $this->initValues($schemaRules),
            'deprecationReason' => $this->deprecationReason(),
            'removalDate' => $this->removalDate(),
        ]);
    }

    /**
     * @throws DefinitionException
     */
    private function initValues(SchemaRules $schemaRules): array
    {
        $definedValues = $this->values();
        if (is_string($definedValues)) {
            return $this->initNativeEnumValues($definedValues);
        }

        $values = [];
        foreach ($definedValues as $key => $definition) {
            $value = $this->initValue($key, $definition);
            if (!$schemaRules->isVisible($value)) {
                continue;
            }

            $values[$value->name] = $value->toDefinition();
        }

        return $values;
    }

    private function initValue(int|string $name, mixed $value): EnumValue {
        return match (true) {
            $value instanceof EnumValue => $value,
            $value instanceof UnitEnum => EnumValue::fromEnum($value),
            is_array($value) => EnumValue::fromDeprecatedConfigArray($value['name'] ?? $name, $value),
            is_int($name) => EnumValue::withName($value)->value($value),
            is_string($name) => EnumValue::withName($name)->value($value),
            default => throw DefinitionException::from($value, 'class-string<UnitEnum>|array<string>|array<EnumValue>|array<array{name: string, (...)}>')
        };
    }

    /**
     * @param class-string<UnitEnum> $enumClassName
     * @return void
     */
    private function initNativeEnumValues(string $enumClassName): array
    {
        if (!enum_exists($enumClassName)) {
            throw DefinitionException::from($enumClassName, 'array<key: string, value: mixed>', 'enum');
        }

        return Arrays::mapWithKeys(
            $enumClassName::cases(),
            fn(int $_, UnitEnum $enum) => [
                $enum->name, ['name' => $enum->name, 'value' => $enum]
            ]);
    }

    public function getName(): string
    {
        return Types::inferNameFromClassName(static::class);
    }

    /**
     * Return a key value array or a serial array containing
     * either the key and the internal value or the keys only.
     *
     * @return array<string,mixed>|class-string<UnitEnum>
     */
    abstract protected function values(): array|string;
}
