<?php declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\SchemaRules;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Utility\Types;

abstract class GraphQlDirective implements DefinesGraphQlType
{
    use HasDescription;

    public function toDefinition(TypeRegistry $registry, SchemaRules $schemaRules): Directive
    {
        $this->verifyLocations();
        return new Directive([
            'name' => $this->getName(),
            'description' => $this->description(),
            'args' => $this->initInputFields($registry),
            'isRepeatable' => $this->isRepeatable(),
            'locations' => $this->locations(),
        ]);
    }

    private function initInputFields(TypeRegistry $registry): array {
        $fields = [];
        foreach ($this->arguments($registry) as $argument) {
            $fields[$argument->getName()] = $argument->toDefinition();
        }
        return $fields;
    }

    private function verifyLocations(): void {
        foreach ($this->locations() as $location) {
            if (!array_key_exists($location, DirectiveLocation::EXECUTABLE_LOCATIONS)) {
                $acceptableValues = implode(', ', DirectiveLocation::EXECUTABLE_LOCATIONS);
                throw new DefinitionException("Expected valid executable location, got: '{$location}'. Acceptable values: {$acceptableValues}");
            }
        }
    }

    /**
     * @return array<InputField>
     */
    abstract protected function arguments(TypeRegistry $registry): array;
    abstract protected function locations(): array;
    public function getName(): string {
        return Types::inferNameFromClassName(static::class);
    }

    protected function isRepeatable(): bool {
        return false;
    }

}