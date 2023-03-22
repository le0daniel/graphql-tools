<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Data\ValueObjects\RawPhpExpression;
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

    public function cache(array $typesToCache, array $aliases, array $extendedFieldsByType = []): array
    {
        $types = [];
        $dependencies = [];

        foreach ($typesToCache as $providedTypeName => $declaration) {
            $declarationTypeName = $declaration instanceof DefinesGraphQlType ? $declaration->getName() : $declaration::typeName();
            $providedTypeName = is_int($providedTypeName) ? $declarationTypeName : $providedTypeName;
            if ($declarationTypeName !== $providedTypeName) {
                throw new RuntimeException("Encountered different name for the type {$providedTypeName} = {$declarationTypeName}.");
            }

            $typeInstance = $declaration instanceof DefinesGraphQlType ? $declaration : new $declaration;
            $compiled = $this->buildType($typeInstance, $aliases, $extendedFieldsByType[$declarationTypeName] ?? []);
            [$typeName, $code, $typeDependencies] = Arrays::unpack($compiled, 'name', 'code', 'typeDependencies');

            $types[$typeName] = $code;
            $dependencies[$typeName] = $typeDependencies;
        }

        return [
            $types,
            $dependencies
        ];
    }

    public function buildType(DefinesGraphQlType $type, array $aliases, array $injectedFields = []): array
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
            'name' => $type->getName(),
            'code' => "static fn() => new {$this->absoluteClassName($type::class)}"
        ];
    }

    private function compileEnum(TypeRegistry $registry, GraphQlEnum $type): array
    {
        $typeDefinition = $type->toDefinition($registry);
        $config = $this->recursivelyInitializeConfig($typeDefinition->config);

        $body = Compiling::exportArray([
            'name' => $typeDefinition->name,
            'removalDate' => $config['removalDate'] ?? null,
            'deprecationReason' => $config['deprecationReason'] ?? null,
            'description' => $config['description'] ?? null,
            'values' => $config['values'],
        ]);

        $code = "static fn({$this->registryNameSpace()} \${$this->typeRegistryName}) => new {$this->absoluteClassName(UnionType::class)}({$body})";

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

        $body = Compiling::exportArray([
            'name' => $typeDefinition->name,
            'removalDate' => $config['removalDate'] ?? null,
            'deprecationReason' => $config['deprecationReason'] ?? null,
            'description' => $config['description'] ?? null,
            'types' => new RawPhpExpression($this->compileListOfType($config['types'])),
            'resolveType' => new RawPhpExpression("static function(\$_, \GraphQlTools\Helper\OperationContext \$context, \$info) use (\${$this->typeRegistryName}) { 
                \$resolver = {$resolveClosure};
                return \${$this->typeRegistryName}->type(\$resolver(\$_, \$context->context, \$info));
            }"),
        ]);

        $code = "static fn({$this->registryNameSpace()} \${$this->typeRegistryName}) => new {$this->absoluteClassName(UnionType::class)}({$body})";

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

        $body = Compiling::exportArray([
            'name' => $typeDefinition->name,
            'deprecationReason' => $config['deprecationReason'] ?? null,
            'removalDate' => $config['removalDate'] ?? null,
            'description' => $config['description'] ?? null,
            'fields' => new RawPhpExpression("static fn() => {$this->compileFieldsToArrayDefinition($config['fields'], false)}"),
            'resolveType' => new RawPhpExpression("static function(\$_, \GraphQlTools\Helper\OperationContext \$context, \$info) use (\${$this->typeRegistryName}) { 
                \$resolver = {$resolveClosure};
                return \${$this->typeRegistryName}->type(\$resolver(\$_, \$context->context, \$info));
            }"),
        ]);

        $code = "static fn({$this->registryNameSpace()} \${$this->typeRegistryName}) => new {$this->absoluteClassName(InterfaceType::class)}({$body})";

        return [
            'name' => $type::typeName(),
            'code' => $code
        ];
    }

    private function compileInputType(TypeRegistry $registry, GraphQlInputType $type): array
    {
        $typeDefinition = $type->toDefinition($registry);
        $config = $this->recursivelyInitializeConfig($typeDefinition->config);
        $inputFields =
            implode(',', array_map(function (array $config): string {
                $code = $this->fieldCompiler->compileInputField($config);
                return "{$this->export($config['name'])} => {$code}";
            }, $config['fields']));

        $body = Compiling::exportArray([
            'name' => $typeDefinition->name,
            'fields' => new RawPhpExpression("[{$inputFields}]"),
            'deprecationReason' => $config['deprecationReason'] ?? null,
            'removalDate' => $config['removalDate'] ?? null,
            'description' => $config['description'] ?? null,
        ]);

        $code = "static fn({$this->registryNameSpace()} \${$this->typeRegistryName}) => new {$this->absoluteClassName(InputObjectType::class)}({$body})";

        return [
            'name' => $typeDefinition->name,
            'code' => $code
        ];
    }

    private function compileType(TypeRegistry $registry, DefinesGraphQlType $type, array $injectedFields = []): array
    {
        $typeDefinition = $type->toDefinition($registry, $injectedFields);
        $config = $this->recursivelyInitializeConfig($typeDefinition->config);

        $body = Compiling::exportArray([
            'name' => $typeDefinition->name,
            'fields' => new RawPhpExpression($this->compileFieldsToArrayDefinition($config['fields'])),
            'interfaces' => new RawPhpExpression($this->compileListOfType($config['interfaces'])),
            'deprecationReason' => $config['deprecationReason'] ?? null,
            'removalDate' => $config['removalDate'] ?? null,
            'description' => $config['description'] ?? null,
        ]);

        $code = "static fn({$this->registryNameSpace()} \${$this->typeRegistryName}) => new {$this->absoluteClassName(ObjectType::class)}({$body})";

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
        $initializedTypes = array_map(static function (mixed $type): RawPhpExpression {
            $type = is_callable($type) ? $type() : $type;
            return new RawPhpExpression((string)$type);
        }, $types);

        return Compiling::exportArray($initializedTypes);
    }

    private function compileFieldsToArrayDefinition(array $fieldDefinitions, bool $withResolveFunction = true): string
    {
        $fields = Arrays::mapWithKeys(
            $fieldDefinitions,
            function ($_, FieldDefinition $definition) use ($withResolveFunction): array {
                $code = $this->fieldCompiler->compileField($definition, $withResolveFunction);
                return [$definition->name, new RawPhpExpression("fn() => {$code}")];
            }
        );

        return Compiling::exportArray($fields);
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