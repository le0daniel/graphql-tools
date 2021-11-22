<?php declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Context;
use GraphQlTools\Resolver\ProxyResolver;
use GraphQlTools\TypeRepository;
use ReflectionClass;

abstract class GraphQlField
{
    public const BETA_FIELD_CONFIG_KEY = 'isBeta';
    public const NOTICE_CONFIG_KEY = 'notice';

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

    final public static function guessFieldName(mixed $name): ?string
    {
        return is_string($name) ? $name : null;
    }

    final public static function isFieldClass(string $className): bool
    {
        $reflection = new ReflectionClass($className);
        return $reflection->isSubclassOf(self::class);
    }

    final public static function isBetaField(FieldDefinition $definition): bool
    {
        return ($definition->config[self::BETA_FIELD_CONFIG_KEY] ?? false) === true;
    }

    final public static function getFieldNotice(FieldDefinition $definition): ?string {
        return $definition->config[self::NOTICE_CONFIG_KEY] ?? null;
    }

    final public function toField(?string $name, TypeRepository $repository): FieldDefinition
    {
        if (!$name && !$this->name()) {
            throw new DefinitionException("A field name must always be provided.");
        }

        return FieldDefinition::create([
            'name' => $name ?? $this->name(),
            'resolve' => new ProxyResolver(fn(...$args) => $this->resolve(...$args)),
            'args' => $this->arguments($repository),
            'type' => $this->fieldType($repository),
            'deprecationReason' => $this->deprecationReason(),
            'description' => $this->description(),

            // Separate config keys for additional value
            self::BETA_FIELD_CONFIG_KEY => $this->isBeta(),
            self::NOTICE_CONFIG_KEY => $this->notice(),
        ]);
    }

}