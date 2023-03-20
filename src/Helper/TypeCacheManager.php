<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\GraphQlEnum;
use GraphQlTools\Definition\GraphQlInputType;
use GraphQlTools\Definition\GraphQlInterface;
use GraphQlTools\Definition\GraphQlScalar;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Definition\GraphQlUnion;
use GraphQlTools\Helper\Compilation\ClosureCompiler;
use GraphQlTools\Helper\Compilation\FieldCompiler;
use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\Compiling;
use RuntimeException;

class TypeCacheManager
{
    private readonly FieldCompiler $fieldCompiler;

    private readonly ClosureCompiler $closureCompiler;

    public function __construct(
        private readonly string $typeRegistryName = 'typeRegistry',
    )
    {
        $this->closureCompiler = new ClosureCompiler();
        $this->fieldCompiler = new FieldCompiler($this->closureCompiler);
        error_reporting(E_ALL ^ E_DEPRECATED);
    }

    private function export(mixed $value): string
    {
        return Compiling::exportVariable($value);
    }

    private function recursiveExport(array $values): string
    {
        $exported = [];
        foreach ($values as $key => $value) {
            $exported[] = is_array($value)
                ? "{$this->export($key)} => {$this->recursiveExport($value)}"
                : "{$this->export($key)} => {$this->export($value)}";
        }

        return '[' . implode(',', $exported) . ']';
    }

    private function absoluteClassName(string $className): string
    {
        return Compiling::absoluteClassName($className);
    }

    private function recursivelyInitializeConfig(array $config, array $blacklistedKeys = ['resolveType']): array
    {
        return Arrays::mapWithKeys($config, function (string|int $key, mixed $value) use ($blacklistedKeys): array {
            if (!$value instanceof Closure || in_array($key, $blacklistedKeys, true)) {
                return [$key, $value];
            }

            $initialized = $value();

            return [
                $key,
                is_array($initialized) ? self::recursivelyInitializeConfig($initialized, []) : $initialized
            ];
        });
    }

    private function allEqual(string ...$values): bool {
        return count(array_unique($values)) === 1;
    }

    public function cache(array $typesToCache, array $aliases, array $extendedFieldsByType = []): array
    {
        $types = [];
        $dependencies = [];

        foreach ($typesToCache as $providedTypeName => $className) {
            $classTypeName = $className::typeName();
            $providedTypeName = is_int($providedTypeName) ? $classTypeName : $providedTypeName;

            $compiled = $this->buildType(new $className, $aliases, $extendedFieldsByType[$classTypeName] ?? []);
            [$typeName, $code, $typeDependencies] = Arrays::unpack($compiled, 'name', 'code', 'typeDependencies');

            if (!$this->allEqual($providedTypeName, $classTypeName, $typeName)) {
                throw new RuntimeException("Encountered different name for the type {$className} = {$typeName}.");
            }

            $types[$typeName] = $code;
            $dependencies[$typeName] = $typeDependencies;
        }

        return [
            $types,
            $dependencies
        ];
    }

    public function buildType(GraphQlType|GraphQlInputType|GraphQlScalar|GraphQlInterface|GraphQlUnion|GraphQlEnum $type, array $aliases, array $injectedFields = []): array
    {
        $registry = $this->mockedTypeRegistry($aliases);
        $compiled = match (true) {
            $type instanceof GraphQlType => $this->compileType($registry, $type, $injectedFields),
            $type instanceof GraphQlInputType => $this->compileInputType($registry, $type),
            $type instanceof GraphQlScalar => $this->compileScalar($registry, $type),
            $type instanceof GraphQlInterface => $this->compileInterface($registry, $type, $injectedFields),
            $type instanceof GraphQlUnion => $this->compileUnion($registry, $type),
            $type instanceof GraphQlEnum => $this->compileEnum($registry, $type),
        };

        [$name, $code] = Arrays::unpack($compiled, 'name', 'code');
        $typeDependencies = $registry->getDependencies();
        return [
            'name' => $name,
            'code' => $code,
            'typeDependencies' => $typeDependencies,
        ];
    }

    private function compileScalar(TypeRegistry $registry, GraphQlScalar $type): array
    {
        return [
            'name' => $type::typeName(),
            'code' => "static fn() => new {$this->absoluteClassName($type::class)}"
        ];
    }

    private function compileEnum(TypeRegistry $registry, GraphQlEnum $type): array
    {
        $typeDefinition = $type->toDefinition($registry);
        $config = $this->recursivelyInitializeConfig($typeDefinition->config);

        $code = "static fn({$this->registryNameSpace()} \${$this->typeRegistryName}) => new {$this->absoluteClassName(UnionType::class)}([
            'name' => {$this->export($typeDefinition->name)},
            'removalDate' => {$this->export($config['removalDate'])},
            'deprecationReason' => {$this->export($config['deprecationReason'] ?? null)},
            'description' => {$this->export($config['description'] ?? null)},
            'values' => {$this->recursiveExport($config['values'])},
        ])";

        return [
            'name' => $typeDefinition->name,
            'code' => $code
        ];
    }

    private function compileUnion(TypeRegistry $registry, GraphQlUnion $type): array
    {
        $typeDefinition = $type->toDefinition($registry);
        $config = $this->recursivelyInitializeConfig($typeDefinition->config);
        $resolveClosure = $this->closureCompiler->compile($type->getResolveTypeClosure());

        $code = "static fn({$this->registryNameSpace()} \${$this->typeRegistryName}) => new {$this->absoluteClassName(UnionType::class)}([
            'name' => {$this->export($typeDefinition->name)},
            'removalDate' => {$this->export($config['removalDate'])},
            'deprecationReason' => {$this->export($config['deprecationReason'] ?? null)},
            'description' => {$this->export($config['description'] ?? null)},
            'types' => static fn() => {$this->compileListOfType($config['types'])},
            'resolveType' => static function(\$_, \GraphQlTools\Helper\OperationContext \$context, \$info) use (\${$this->typeRegistryName}) { 
                \$resolver = {$resolveClosure};
                return \${$this->typeRegistryName}->type(\$resolver(\$_, \$context->context, \$info));
            },
        ])";

        return [
            'name' => $typeDefinition->name,
            'code' => $code
        ];
    }

    private function compileInterface(TypeRegistry $registry, GraphQlInterface $type, array $injectedFields): array
    {
        $typeDefinition = $type->toDefinition($registry, $injectedFields);
        $config = $this->recursivelyInitializeConfig($typeDefinition->config);

        $resolveClosure = $this->closureCompiler->compile($type->getResolveTypeClosure());

        $code = "static fn({$this->registryNameSpace()} \${$this->typeRegistryName}) => new {$this->absoluteClassName(InterfaceType::class)}([
            'name' => {$this->export($typeDefinition->name)},
            'deprecationReason' => {$this->export($config['deprecationReason'] ?? null)},
            'removalDate' => {$this->export($config['removalDate'])},
            'description' => {$this->export($config['description'] ?? null)},
            'fields' => static fn() => {$this->compileFieldsToArrayDefinition($config['fields'], false)},
            'resolveType' => static function(\$_, \GraphQlTools\Helper\OperationContext \$context, \$info) use (\${$this->typeRegistryName}) { 
                \$resolver = {$resolveClosure};
                return \${$this->typeRegistryName}->type(\$resolver(\$_, \$context->context, \$info));
            },
        ])";

        return [
            'name' => $type::typeName(),
            'code' => $code
        ];
    }

    private function compileInputType(TypeRegistry $registry, GraphQlInputType $type): array
    {
        $typeDefinition = $type->toDefinition($registry);
        $config = $this->recursivelyInitializeConfig($typeDefinition->config);
        $inputFields = implode(',', array_map(function (array $config): string {
            $code = $this->fieldCompiler->compileInputField($config);
            return "{$this->export($config['name'])} => {$code}";
        }, $config['fields']));

        $code = "static fn({$this->registryNameSpace()} \${$this->typeRegistryName}) => new {$this->absoluteClassName(InputObjectType::class)}([
            'name' => {$this->export($typeDefinition->name)},
            'fields' => static fn() => [{$inputFields}],
            'deprecationReason' => {$this->export($config['deprecationReason'] ?? null)},
            'removalDate' => {$this->export($config['removalDate'])},
            'description' => {$this->export($config['description'] ?? null)},
        ])";

        return [
            'name' => $typeDefinition->name,
            'code' => $code
        ];
    }

    private function compileType(TypeRegistry $registry, GraphQlType $type, array $injectedFields = []): array
    {
        $typeDefinition = $type->toDefinition($registry, $injectedFields);
        $config = $this->recursivelyInitializeConfig($typeDefinition->config);

        $code = "static fn({$this->registryNameSpace()} \${$this->typeRegistryName}) => new {$this->absoluteClassName(ObjectType::class)}([
            'name' => {$this->export($typeDefinition->name)},
            'fields' => static fn() => {$this->compileFieldsToArrayDefinition($config['fields'])},
            'interfaces' => static fn() => {$this->compileListOfType($config['interfaces'])},
            'deprecationReason' => {$this->export($config['deprecationReason'] ?? null)},
            'removalDate' => {$this->export($config['removalDate'])},
            'description' => {$this->export($config['description'] ?? null)},
        ])";

        return [
            'name' => $typeDefinition->name,
            'code' => $code
        ];
    }

    private function registryNameSpace(): string
    {
        return $this->absoluteClassName(TypeRegistry::class);
    }

    private function compileListOfType(array $types): string
    {
        $initializedTypes = array_map(static function (mixed $type): string {
            $type = is_callable($type) ? $type() : $type;
            return (string)$type;
        }, $types);

        return "[" . implode(',', $initializedTypes) . "]";
    }

    private function compileFieldsToArrayDefinition(array $fieldDefinitions, bool $withResolveFunction = true): string
    {
        $fields = array_map(function (FieldDefinition $definition) use ($withResolveFunction): string {
            $code = $this->fieldCompiler->compileField($definition, $withResolveFunction);
            return "{$this->export($definition->name)} => fn() => {$code}";
        }, $fieldDefinitions);
        return '[' . implode(',', $fields,) . ']';
    }

    public function mockedTypeRegistry(array $aliases): TypeRegistry
    {
        return new class ($this->typeRegistryName, $aliases) implements TypeRegistry {
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
        };
    }
}