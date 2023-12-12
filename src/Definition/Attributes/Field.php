<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Attributes;

use Attribute;
use Closure;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Utility\Debugging;
use GraphQlTools\Utility\Types;
use ReflectionMethod;
use ReflectionNamedType;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Field
{
    public function __construct(private ?string $typeString = null)
    {
    }

    /**
     * @throws DefinitionException
     */
    public function getType(TypeRegistry $registry, ReflectionMethod $method): Closure|Type
    {
        if (!$this->typeString) {
            return $this->inferFromReturnType($registry, $method);
        }

        [$name, $decorators] = Types::parseGraphQlTypeDefinition($this->typeString);

        if (empty($decorators)) {
            return $registry->type($name);
        }

        return array_reduce(array_reverse($decorators), fn($type, string $decorator) => match ($decorator) {
            'NonNull' => new NonNull($type),
            'List' => new ListOfType($type),
        }, $registry->type($name));
    }

    /**
     * @throws DefinitionException
     */
    private function inferFromReturnType(TypeRegistry $registry, ReflectionMethod $reflection): NonNull|ScalarType|Closure
    {
        $returnType = $reflection->getReturnType();
        if (!$returnType instanceof ReflectionNamedType || !$returnType->isBuiltin()) {
            throw new DefinitionException('Can not infer return type from non ReflectionNamedType that are not built in types, got: ' . Debugging::typeOf($returnType));
        }

        return match ($returnType->getName()) {
            'string' => $this->wrapNonNull($returnType, $registry->string()),
            'int' => $this->wrapNonNull($returnType, $registry->int()),
            'float' => $this->wrapNonNull($returnType, $registry->float()),
            'boolean', 'false', 'true' => $this->wrapNonNull($returnType, $registry->boolean()),
            default => throw new DefinitionException("Expected valid built in type (string, int, float, boolean, false, true), got: '{$returnType->getName()}'"),
        };
    }

    private function wrapNonNull(ReflectionNamedType $returnType, mixed $type): mixed
    {
        return $returnType->allowsNull() ? $type : new NonNull($type);
    }

}