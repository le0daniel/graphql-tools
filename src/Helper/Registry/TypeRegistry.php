<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Registry;

use Closure;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Contract\TypeRegistry as TypeRegistryContract;
use GraphQlTools\Definition\GraphQlInputType;
use GraphQlTools\Definition\GraphQlInterface;
use GraphQlTools\Definition\GraphQlScalar;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Definition\GraphQlUnion;
use RuntimeException;

class TypeRegistry implements TypeRegistryContract
{
    private array $typeInstances = [];

    public function __construct(
        private readonly array $typeNameResolution,
        private readonly array $reverseTypeNameResolution
    )
    {
    }

    public function shouldHideField(Field $field): bool
    {
        return false;
    }

    public function shouldHideInputField(InputField $inputField): bool
    {
        return false;
    }

    public function type(string $classOrTypeName): Closure|Type
    {
        return fn() => $this->getType(
            $this->resolveTypeName($classOrTypeName)
        );
    }

    public function eagerlyLoadType(string $classOrTypeName): Type
    {
        return $this->getType(
            $this->resolveTypeName($classOrTypeName)
        );
    }

    protected function resolveTypeName(string $classOrTypeName): string
    {
        return $this->reverseTypeNameResolution[$classOrTypeName] ?? $classOrTypeName;
    }

    protected function getType(string $typeName): Type
    {
        if (!isset($this->typeInstances[$typeName])) {
            $this->typeInstances[$typeName] = $this->createInstanceOfType($typeName);
        }

        return $this->typeInstances[$typeName];
    }

    protected function createInstanceOfType(string $typeName): Type
    {
        $typeFactory = $this->typeNameResolution[$typeName] ?? null;
        if (!$typeFactory) {
            throw new RuntimeException("Could not resolve type '{$typeName}', no factory provided. Did you register this type?");
        }

        if (is_callable($typeFactory)) {
            return $typeFactory($this);
        }

        /** @var GraphQlType|GraphQlScalar|GraphQlInputType|GraphQlInterface|GraphQlUnion $instance */
        $instance = new $typeFactory;
        if ($instance instanceof GraphQlScalar) {
            return $instance;
        }

        return $instance->toDefinition($this);
    }
}