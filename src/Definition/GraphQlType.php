<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use Closure;
use GraphQL\Type\Definition\ObjectType;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\SchemaRules;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Data\ValueObjects\GraphQlTypes;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Shared\HasDeprecation;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Definition\Shared\InitializesFields;
use GraphQlTools\Definition\Shared\MergesFields;
use GraphQlTools\Utility\Types;

abstract class GraphQlType implements DefinesGraphQlType
{
    use HasDescription, HasDeprecation, InitializesFields, MergesFields;

    protected function middleware(): array|null {
        return null;
    }

    /**
     * Return an array of fields of that specific type. The fields
     * are then initialized correctly and a proxy attached to them.
     *
     * @return Field[]|array<string, callable(string, TypeRegistry): Field>
     */
    abstract protected function fields(TypeRegistry $registry): array;

    private function getDefinedFields(TypeRegistry $registry): array {
        if (empty($this->middleware())) {
            return $this->fields($registry);
        }

        /** @var array<Closure> $middleware */
        $middleware = $this->middleware();
        return array_map(fn(Field $field) => $field->prependMiddleware(...$middleware), $this->fields($registry));
    }

    public function toDefinition(TypeRegistry $registry, SchemaRules $schemaRules): ObjectType {
        return new ObjectType(
            [
                'name' => $this->getName(),
                'description' => $this->addDeprecationToDescription($this->description()),
                'deprecationReason' => $this->deprecationReason(),
                'removalDate' => $this->removalDate(),
                'fields' => fn() => $this->initializeFields(
                    $registry,
                    [$this->getDefinedFields(...), ...$this->mergedFieldFactories],
                    $schemaRules
                ),
                'interfaces' => fn() => array_map(
                    fn(string $interfaceName) => $registry->type($interfaceName, GraphQlTypes::INTERFACE),
                    $this->interfaces()
                ),
            ]
        );
    }

    final public function getInterfaces(): array {
        return $this->interfaces();
    }

    public function getName(): string
    {
        return Types::inferNameFromClassName(static::class);
    }

    /**
     * Array returning the Interface types resolved by the TypeRepository.
     * @return array
     */
    protected function interfaces(): array
    {
        return [];
    }

}
