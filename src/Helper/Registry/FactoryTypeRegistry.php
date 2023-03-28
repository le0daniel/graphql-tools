<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Registry;

use Closure;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\TypeRegistry as TypeRegistryContract;
use GraphQlTools\Definition\DefinitionException;
use RuntimeException;

class FactoryTypeRegistry implements TypeRegistryContract
{
    private array $typeInstances = [];

    /**
     * @param array<string, callable|class-string> $types
     * @param array<string, string> $aliasesOfTypes
     */
    public function __construct(
        private readonly array $types,
        private readonly array $aliasesOfTypes = []
    )
    {
    }

    public function verifyAliasCollisions(): void {
        foreach ($this->aliasesOfTypes as $alias => $typeName) {
            if (array_key_exists($alias, $this->types)) {
                throw new RuntimeException("The alias `{$alias}` is used also as typename, which is invalid.");
            }
        }
    }

    public function type(string $nameOrAlias): Closure
    {
        return fn() => $this->getType(
            $this->resolveTypeNameAliases($nameOrAlias)
        );
    }

    protected function resolveTypeNameAliases(string $nameOrAlias): string
    {
        return $this->aliasesOfTypes[$nameOrAlias] ?? $nameOrAlias;
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
        $typeFactory = $this->types[$typeName] ?? null;
        if (!$typeFactory) {
            throw new DefinitionException("Could not resolve type '{$typeName}', no factory provided. Did you register this type?");
        }
        return $typeFactory($this);
    }
}