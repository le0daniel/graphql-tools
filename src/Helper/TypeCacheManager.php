<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use DateTimeInterface;
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
use RuntimeException;

class TypeCacheManager
{
    private readonly FieldCompiler $fieldCompiler;

    private readonly ClosureCompiler $closureCompiler;

    public function __construct(
        private readonly string $typeRegistryName = 'typeRegistry',
        private readonly bool   $lazyFields = true,
    )
    {
        $this->closureCompiler = new ClosureCompiler();
        $this->fieldCompiler = new FieldCompiler($this->closureCompiler);
        error_reporting(E_ALL ^ E_DEPRECATED);
    }

    private function export(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return "\DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '{$value->format('Y-m-d H:i:s')}')";
        }

        $exported = var_export($value, true);
        if (preg_match('/^[a-zA-Z0-9]+\\\\[a-zA-Z0-9:\\\\]+$/', $exported)) {
            return '\\' . $exported;
        }

        return $exported;
    }

    private function recursiveExport(array $values): string {
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
        return str_starts_with($className, '\\') ? $className : "\\$className";
    }

    private function initializeConfig(array $config, array $blacklistedKeys = ['resolveType']): array
    {
        return Arrays::mapWithKeys($config,function(string|int $key, mixed $value) use ($blacklistedKeys): array {
            if (!$value instanceof Closure || in_array($key, $blacklistedKeys, true)) {
                return [$key, $value];
            }

            $initialized = $value();

            return [$key, is_array($initialized) ? self::initializeConfig($initialized, []): $initialized];
        });
    }

    public function cache(array $typesToCache, array $extendedFieldsByType = []) {
        $aliases = [];
        $types = [];

        foreach ($typesToCache as $className) {
            $typeName = $className::typeName();
            $typeExtensions = [
                ... $extendedFieldsByType[$typeName] ?? [],
                ... $extendedFieldsByType[$className] ?? [],
            ];


            $compiled = $this->buildType(new $className, $typeExtensions);
            $typeName = $compiled['name'];

            foreach ($compiled['aliases'] as $alias) {
                $aliases[$alias] = $typeName;
            }

            $types[$typeName] = $compiled['code'];
        }

        return [
            $aliases,
            $types
        ];
    }

    public function buildType(mixed $type, array $injectedFields = []): array
    {
        if ($type instanceof GraphQlType) {
            return $this->compileType($type, $injectedFields);
        }
        if ($type instanceof GraphQlInputType) {
            return $this->compileInputType($type);
        }
        if ($type instanceof GraphQlScalar) {
            return $this->compileScalar($type);
        }
        if ($type instanceof GraphQlInterface) {
            return $this->compileInterface($type, $injectedFields);
        }
        if ($type instanceof GraphQlUnion) {
            return $this->compileUnion($type);
        }
        if ($type instanceof GraphQlEnum) {
            return $this->compileEnum($type);
        }

        throw new RuntimeException("Unsupported type given.");
    }

    private function compileScalar(GraphQlScalar $type): array {
        return [
            'name' => $type::typeName(),
            'aliases' => [$type::class],
            'code' => "static fn() => new {$this->absoluteClassName($type::class)}"
        ];
    }

    private function compileEnum(GraphQlEnum $type): array {
        $registry = $this->collectorRegistry();
        $typeDefinition = $type->toDefinition($registry);
        $config = $this->initializeConfig($typeDefinition->config);

        $code = "static fn({$this->registryNameSpace()} \${$this->typeRegistryName}) => new {$this->absoluteClassName(UnionType::class)}([
            'name' => {$this->export($typeDefinition->name)},
            'removalDate' => {$this->export($config['removalDate'])},
            'deprecationReason' => {$this->export($config['deprecationReason'] ?? null)},
            'description' => {$this->export($config['description'] ?? null)},
            'values' => {$this->recursiveExport($config['values'])},
        ])";

        return [
            'name' => $typeDefinition->name,
            'aliases' => [$type::class],
            'code' => $code
        ];
    }

    private function compileUnion(GraphQlUnion $type): array {
        $registry = $this->collectorRegistry();
        $typeDefinition = $type->toDefinition($registry);
        $config = $this->initializeConfig($typeDefinition->config);
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
            'aliases' => [$type::class],
            'code' => $code
        ];
    }

    private function compileInterface(GraphQlInterface $type, array $injectedFields): array {
        $registry = $this->collectorRegistry();
        $typeDefinition = $type->toDefinition($registry, $injectedFields);
        $config = $this->initializeConfig($typeDefinition->config);

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
            'aliases' => [$type::class],
            'code' => $code
        ];
    }

    private function compileInputType(GraphQlInputType $type): array {
        $registry = $this->collectorRegistry();
        $typeDefinition = $type->toDefinition($registry);
        $config = $this->initializeConfig($typeDefinition->config);
        $inputFields = implode(',', array_map(function(array $config): string {
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
            'aliases' => [$type::class],
            'code' => $code
        ];
    }

    private function compileType(GraphQlType $type, array $injectedFields = []): array
    {
        $registry = $this->collectorRegistry();
        $typeDefinition = $type->toDefinition($registry, $injectedFields);
        $config = $this->initializeConfig($typeDefinition->config);

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
            'aliases' => [$type::class],
            'code' => $code
        ];
    }

    private function registryNameSpace(): string
    {
        return $this->absoluteClassName(TypeRegistry::class);
    }

    private function compileListOfType(array $types): string
    {
        $compiled = array_map(fn(mixed $type) => is_callable($type) ? $type() : $type, $types);
        $imploded = implode(',', $compiled);
        return "[{$imploded}]";
    }

    private function compileFieldsToArrayDefinition(array $fieldDefinitions, bool $withResolveFunction = true): string
    {
        $fields = array_map(function (FieldDefinition $definition) use ($withResolveFunction): string {
            $code = $this->fieldCompiler->compileField($definition, $withResolveFunction);
            return $this->lazyFields
                ? "{$this->export($definition->name)} => fn() => {$code}"
                : $code;
        }, $fieldDefinitions);
        return '[' . implode(',', $fields,) . ']';
    }

    public function collectorRegistry()
    {
        return new class ($this->typeRegistryName) implements TypeRegistry {
            private array $typeDependencies = [];

            public function __construct(private readonly string $typeRegistryVariableName = 'registry')
            {
            }

            public function getTypeDependencies(): array
            {
                return array_unique($this->typeDependencies);
            }

            public function type(string $nameOrAlias): Closure
            {
                $exportedTypeName = var_export($nameOrAlias, true);
                $code = "\${$this->typeRegistryVariableName}->type({$exportedTypeName})";

                // It is needed to return a type for lists, because the ListOfType does validation
                // As it eagerly creates the error message, which does check the types.
                return fn() => new class($code) extends Type {
                    public function __construct(private readonly string $code)
                    {
                    }

                    public function assertValid()
                    {
                        return true;
                    }

                    public function __toString()
                    {
                        return $this->code;
                    }
                };
            }

            public function eagerlyLoadType(string $nameOrAlias): Type
            {
                throw new RuntimeException("This method is internal and should not be used.");
            }
        };
    }
}