<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use Closure;
use GraphQL\Type\Definition\ObjectType;
use GraphQlTools\Contract\SchemaRules;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Data\ValueObjects\GraphQlTypes;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Shared\HasFields;
use GraphQlTools\Utility\Types;

abstract class GraphQlType extends TypeDefinition
{
    use HasFields;

    protected function middleware(): array|null {
        return null;
    }

    /**
     * Overwrite if you want to define custom name patterns
     * @return string
     * @throws DefinitionException
     */
    public function getName(): string
    {
        return Types::inferNameFromClassName(static::class);
    }

    /**
     * Overwrite to define interfaces implemented by this type.
     * Return class-names or interface names.
     * @return array<string|class-string<GraphQlInterface>>
     */
    protected function interfaces(): array
    {
        return [];
    }

    private function getDefinedFields(TypeRegistry $registry): array {
        if (empty($this->middleware())) {
            return $this->fields($registry);
        }

        /** @var array<Closure> $middleware */
        $middleware = $this->middleware();
        return array_map(fn(Field $field) => $field->prependMiddleware(...$middleware), $this->fields($registry));
    }

    final public function toDefinition(TypeRegistry $registry, SchemaRules $schemaRules): ObjectType {
        return new ObjectType(
            [
                'name' => $this->getName(),
                'description' => $this->computeDescription(),
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

}
