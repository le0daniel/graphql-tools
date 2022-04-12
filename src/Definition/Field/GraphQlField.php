<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\Field\Shared\DefinesArguments;
use GraphQlTools\Definition\Field\Shared\DefinesField;
use GraphQlTools\Definition\Field\Shared\DefinesMetadata;
use GraphQlTools\Definition\Field\Shared\DefinesNotice;
use GraphQlTools\Definition\Field\Shared\DefinesReturnType;
use GraphQlTools\Helper\ProxyResolver;
use GraphQlTools\TypeRegistry;
use GraphQlTools\Utility\Fields;
use JetBrains\PhpStorm\Pure;

abstract class GraphQlField
{
    use DefinesField, DefinesReturnType, DefinesMetadata, DefinesNotice, DefinesArguments;

    final protected function __construct(public readonly string $name)
    {
    }

    #[Pure]
    final public static function withName(string $name): static
    {
        return new static($name);
    }

    final public function isHidden(TypeRegistry $repository): bool {
        return $this->hideBecauseOfDeprecation() || $repository->shouldHideField($this->schemaVariant, $this->metadata);
    }

    final public function toFieldDefinition(TypeRegistry $repository, bool $withoutResolver = false): FieldDefinition
    {
        if (!isset($this->ofType)) {
            throw DefinitionException::fromMissingFieldDeclaration('ofType', $this->name, 'Every field must have a type defined.');
        }

        return FieldDefinition::create([
            'name' => $this->name,
            'resolve' => $withoutResolver ? null : $this->getResolver(),
            'type' => $this->resolveReturnType($repository),
            'deprecationReason' => $this->computeDeprecationReason(),
            'description' => $this->computeDescription(),
            'args' => $this->buildArguments($repository),

            // Separate config keys for additional value
            Fields::SCHEMA_VARIANT => $this->schemaVariant,
            Fields::NOTICE_CONFIG_KEY => $this->notice,
            Fields::METADATA_CONFIG_KEY => $this->metadata,
        ]);
    }

    abstract protected function getResolver(): ProxyResolver;

}