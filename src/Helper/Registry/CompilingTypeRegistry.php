<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Registry;

use Closure;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Utility\Compiling;
use RuntimeException;

final class CompilingTypeRegistry implements TypeRegistry
{

    private array $dependencies = [];

    public function __construct(private readonly string $typeRegistryVariableName, private readonly array $aliases)
    {
    }

    /**
     * @return array
     */
    public function getDependencies(): array
    {
        return array_values(array_unique($this->dependencies));
    }

    public function type(string $nameOrAlias): Closure
    {
        $typeName = $this->aliases[$nameOrAlias] ?? $nameOrAlias;
        $this->dependencies[] = $typeName;
        $exportedTypeName = Compiling::exportVariable($typeName);
        return fn() => "\${$this->typeRegistryVariableName}->type({$exportedTypeName})";
    }

    public function eagerlyLoadType(string $nameOrAlias): Type
    {
        throw new RuntimeException("This method is internal and should not be used.");
    }

}