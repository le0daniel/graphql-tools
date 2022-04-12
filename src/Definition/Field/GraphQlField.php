<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQlTools\Definition\Field\Shared\DefinesArguments;
use GraphQlTools\Definition\Field\Shared\DefinesField;
use GraphQlTools\Definition\Field\Shared\DefinesMetadata;
use GraphQlTools\Definition\Field\Shared\DefinesNotice;
use GraphQlTools\Definition\Field\Shared\DefinesReturnType;
use GraphQlTools\Helper\ProxyResolver;
use GraphQlTools\TypeRepository;
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

    final public function isHidden(TypeRepository $repository): bool {
        return $this->hideBecauseOfDeprecation() || $repository->shouldHideField($this->isBeta, $this->metadata);
    }

    final public function toFieldDefinition(TypeRepository $repository, bool $withoutResolver = false): FieldDefinition
    {
        return FieldDefinition::create([
            'name' => $this->name,
            'resolve' => $withoutResolver ? null : $this->getResolver(),
            'type' => $this->resolveReturnType($repository),
            'deprecationReason' => $this->computeDeprecationReason(),
            'description' => $this->computeDescription(),
            'args' => $this->buildArguments($repository),

            // Separate config keys for additional value
            Fields::BETA_FIELD_CONFIG_KEY => $this->isBeta,
            Fields::NOTICE_CONFIG_KEY => $this->notice,
            Fields::METADATA_CONFIG_KEY => $this->metadata,
        ]);
    }

    abstract protected function getResolver(): ProxyResolver;

}