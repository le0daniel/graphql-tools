<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use BackedEnum;
use GraphQL\Type\Definition\EnumType;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\EnumValue;
use GraphQlTools\Definition\Shared\HasDeprecation;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\Types;
use GraphQlTools\Utility\Typing;
use UnitEnum;

abstract class GraphQlEnum implements DefinesGraphQlType
{
    use HasDescription, HasDeprecation;

    public function toDefinition(TypeRegistry $registry): EnumType
    {
        return new EnumType([
            'name' => $this->getName(),
            'description' => $this->addDeprecationToDescription($this->description()),
            'values' => fn() => $this->initValues(),
            'deprecationReason' => $this->deprecationReason(),
            'removalDate' => $this->removalDate(),
        ]);
    }

    /**
     * @return array<string, array<{key: string, value: mixed}>>
     * @throws DefinitionException
     */
    private function initValues(): array
    {
        $definedValues = $this->values();
        if (is_string($definedValues)) {
            return $this->initNativeEnumValues($definedValues);
        }

        $values = [];
        foreach ($definedValues as $name => $value) {
            if ($value instanceof EnumValue) {
                $values[$value->name] = $value->toDefinition();
                continue;
            }

            if ($value instanceof UnitEnum) {
                $enumValue = EnumValue::fromEnum($value);
                $values[$enumValue->name] = $enumValue->toDefinition();
                continue;
            }

            if (is_array($value)) {
                $value['name'] = $value['name'] ?? $name;
                Typing::verifyIsString($value['name']);
                $values[$value['name']] = $value;
                continue;
            }

            if (is_int($name)) {
                $values[$value] = ['name' => $value, 'value' => $value];
                continue;
            }

            if (is_string($name)) {
                $values[$name] = ['name' => $name, 'value' => $value];
                continue;
            }

            // Legacy Case with array
            throw DefinitionException::from($value, 'class-string<UnitEnum>|array<string>|array<EnumValue>|array<array{name: string, (...)}>');
        }

        return $values;
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
     * @return array<string,mixed>|class-string<BackedEnum>
     */
    abstract protected function values(): array|string;
}
