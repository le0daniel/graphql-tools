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

    abstract protected function fieldType(TypeRepository $repository);

    abstract protected function resolve(mixed $typeData, array $arguments, Context $context, ResolveInfo $info);

    protected function arguments(TypeRepository $repository): ?array {
        return null;
    }

    protected function name(): ?string {
        return null;
    }

    protected function deprecationReason(): ?string {
        return null;
    }

    protected function isBeta(): bool {
        return false;
    }

    final public static function guessFieldName(mixed $name): ?string {
        return is_string($name) ? $name : null;
    }

    final public static function isFieldClass(string $className): bool {
        $reflection = new ReflectionClass($className);
        return $reflection->isSubclassOf(GraphQlField::class);
    }

    final public function toField(?string $name, TypeRepository $repository): FieldDefinition {
        if (!$name && !$this->name()) {
            throw new DefinitionException("A field name must always be provided.");
        }

        return FieldDefinition::create([
            'name' => $name ?? $this->name(),
            'resolve' => new ProxyResolver(fn(...$args) => $this->resolve(...$args)),
            'args' => $this->arguments($repository),
            'type' => $this->fieldType($repository),
            'deprecationReason' => $this->deprecationReason(),
            self::BETA_FIELD_CONFIG_KEY => $this->isBeta(),
        ]);
    }

}