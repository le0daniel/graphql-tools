<?php declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Context;
use GraphQlTools\Definition\Field\Fieldable;
use GraphQlTools\Resolver\ProxyResolver;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Fields;
use ReflectionClass;

abstract class GraphQlField implements Fieldable
{
    /**
     * Define the type of the field. You can either return a classname / typename
     * or use the TypeRepository to get the correct type.
     *
     * @param TypeRepository $repository
     * @return mixed
     */
    abstract protected function fieldType(TypeRepository $repository);

    /**
     * Resolve the field to the correct value.
     *
     * @param mixed $typeData
     * @param array $arguments
     * @param Context $context
     * @param ResolveInfo $info
     * @return mixed
     */
    abstract protected function resolve(mixed $typeData, array $arguments, Context $context, ResolveInfo $info);

    /**
     * Return a description for this field.
     *
     * @return string|null
     */
    abstract protected function description(): ?string;

    /**
     * Define an array of arguments
     *
     * @param TypeRepository $repository
     * @return array|null
     */
    protected function arguments(TypeRepository $repository): ?array
    {
        return null;
    }

    /**
     * Optional, name of the field. This is used as a Fallback if no
     * name has been defined when using the field
     *
     * @return string|null
     */
    protected function name(): ?string
    {
        return null;
    }

    /**
     * If the field is deprecated, return a string
     * @return string|null
     */
    protected function deprecationReason(): ?string
    {
        return null;
    }

    /**
     * Defines if this field is in BETA or not.
     *
     * @return bool
     */
    protected function isBeta(): bool
    {
        return false;
    }

    /**
     * Adds a notice, which is caught by the FieldMessages extension
     *
     * @return string|null
     */
    protected function notice(): ?string {
        return null;
    }

    /**
     * Return Field metadata. Must be json serializable.
     *
     * @return mixed
     */
    protected function metadata(): mixed {
        return null;
    }

    final public function toField(?string $name, TypeRepository $repository): FieldDefinition
    {
        if (!$name && !$this->name()) {
            throw new DefinitionException("A field name must always be provided.");
        }

        return FieldDefinition::create([
            'name' => $name ?? $this->name(),
            'resolve' => new ProxyResolver($this->resolve(...)),
            'args' => $this->arguments($repository),
            'type' => $this->fieldType($repository),
            'deprecationReason' => $this->deprecationReason(),
            'description' => $this->description(),

            // Separate config keys for additional value
            Fields::BETA_FIELD_CONFIG_KEY => $this->isBeta(),
            Fields::NOTICE_CONFIG_KEY => $this->notice(),
            Fields::METADATA_CONFIG_KEY => $this->metadata(),
        ]);
    }

}