<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\UnionType;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Data\ValueObjects\RawPhpExpression;
use GraphQlTools\Helper\Compilation\ClosureCompiler;
use GraphQlTools\Helper\Compilation\FieldCompiler;
use GraphQlTools\Helper\Registry\CompilingTypeRegistry;
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

    private function recursivelyInitializeConfig(array $config): array
    {
        return Arrays::mapWithKeys($config, function (string|int $key, mixed $value): array {
            if (!$value instanceof Closure || in_array($key, ['resolveType', 'resolveFn'], true)) {
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
            $typeInstance = $declaration instanceof DefinesGraphQlType ? $declaration : new $declaration;
            $declarationTypeName = $typeInstance->getName();
            if ($declarationTypeName !== $providedTypeName) {
                throw new RuntimeException("Encountered different name for the type {$providedTypeName} = {$declarationTypeName}.");
            }

            ['name' => $typeName, 'code' => $code, 'typeDependencies' => $typeDependencies] = $this->buildType($typeInstance, $aliases, $extendedFieldsByType[$declarationTypeName] ?? []);
            $types[$typeName] = $code;
            $dependencies[$typeName] = $typeDependencies;
        }

        return [
            $types,
            $dependencies
        ];
    }

    /**
     * @param DefinesGraphQlType $type
     * @param array $aliases
     * @param array $injectedFields
     * @return array{'name': string, 'code': string, 'typeDependencies': array<string>}
     */
    public function buildType(DefinesGraphQlType $type, array $aliases, array $injectedFields = []): array
    {
        $registry = new CompilingTypeRegistry($this->typeRegistryName, $aliases);
        $declaration = $type->toDefinition($registry, $injectedFields);

        $compiled = match (true) {
            $declaration instanceof ObjectType => $this->compileObjectType($declaration),
            $declaration instanceof InputObjectType => $this->compileInputType($declaration),
            $declaration instanceof ScalarType => $this->compileScalar($declaration),
            $declaration instanceof InterfaceType => $this->compileInterface($declaration),
            $declaration instanceof UnionType => $this->compileUnion($declaration),
            $declaration instanceof EnumType => $this->compileEnum($declaration),
            default => throw new RuntimeException("Could not compile: " . $declaration::class)
        };

        ['name' => $name, 'code' => $code] = $compiled;
        return [
            'name' => $name,
            'code' => $code,
            'typeDependencies' => $registry->getDependencies(),
        ];
    }

    private function compileScalar(ScalarType $type): array
    {
        $className = Compiling::absoluteClassName($type::class);
        return [
            'name' => $type->name,
            'code' => "static function() { return new {$className}; }"
        ];
    }

    private function compileEnum(EnumType $typeDefinition): array
    {
        $config = $this->recursivelyInitializeConfig($typeDefinition->config);

        return [
            'name' => $typeDefinition->name,
            'code' => $this->compileToTypeFunction(
                UnionType::class,
                [
                    'name' => $typeDefinition->name,
                    'removalDate' => $config['removalDate'] ?? null,
                    'deprecationReason' => $config['deprecationReason'] ?? null,
                    'description' => $config['description'] ?? null,
                    'values' => $config['values'],
                ]
            )
        ];
    }

    private function compileUnion(UnionType $typeDefinition): array
    {
        $config = $this->recursivelyInitializeConfig($typeDefinition->config);

        return [
            'name' => $typeDefinition->name,
            'code' => $this->compileToTypeFunction(UnionType::class, [
                'name' => $typeDefinition->name,
                'removalDate' => $config['removalDate'] ?? null,
                'deprecationReason' => $config['deprecationReason'] ?? null,
                'description' => $config['description'] ?? null,
                'types' => $this->compileListOfType($config['types']),
                'resolveType' => new RawPhpExpression("static function(\$_, \GraphQlTools\Helper\OperationContext \$context, \$info) use (\${$this->typeRegistryName}) { 
                    return \${$this->typeRegistryName}->type({$this->compileResolveTypeFunction($config['resolveFn'])}(\$_, \$context->context, \$info));
                }"),
            ])
        ];
    }

    private function compileInterface(InterfaceType $typeDefinition): array
    {
        $config = $this->recursivelyInitializeConfig($typeDefinition->config);
        $resolveTypeBody = $this->compileResolveTypeFunction($config['resolveFn']);

        return [
            'name' => $typeDefinition->name,
            'code' => $this->compileToTypeFunction(
                InterfaceType::class,
                [
                    'name' => $typeDefinition->name,
                    'deprecationReason' => $config['deprecationReason'] ?? null,
                    'removalDate' => $config['removalDate'] ?? null,
                    'description' => $config['description'] ?? null,
                    'fields' => new RawPhpExpression("static fn() => {$this->compileFieldsToArrayDefinition($config['fields'], false)}"),
                    'resolveType' => new RawPhpExpression("static function(\$_, \GraphQlTools\Helper\OperationContext \$context, \$info) use (\${$this->typeRegistryName}) { 
                        return \${$this->typeRegistryName}->type({$resolveTypeBody}(\$_, \$context->context, \$info));
                    }"),
                ]
            )
        ];
    }

    private function compileInputType(InputObjectType $typeDefinition): array
    {
        $config = $this->recursivelyInitializeConfig($typeDefinition->config);
        $inputFields = $this->compileInputFieldsToArrayDefinition($config['fields']);

        return [
            'name' => $typeDefinition->name,
            'code' => $this->compileToTypeFunction(
                InputObjectType::class,
                [
                    'name' => $typeDefinition->name,
                    'fields' => new RawPhpExpression("fn() => {$inputFields}"),
                    'deprecationReason' => $config['deprecationReason'] ?? null,
                    'removalDate' => $config['removalDate'] ?? null,
                    'description' => $config['description'] ?? null,
                ]
            )
        ];
    }

    private function compileObjectType(ObjectType $typeDefinition): array
    {
        $config = $this->recursivelyInitializeConfig($typeDefinition->config);
        return [
            'name' => $typeDefinition->name,
            'code' => $this->compileToTypeFunction(
                ObjectType::class,
                [
                    'name' => $typeDefinition->name,
                    'fields' => new RawPhpExpression("static fn() => {$this->compileFieldsToArrayDefinition($config['fields'])}"),
                    'interfaces' => $this->compileListOfType($config['interfaces']),
                    'deprecationReason' => $config['deprecationReason'] ?? null,
                    'removalDate' => $config['removalDate'] ?? null,
                    'description' => $config['description'] ?? null,
                ]
            )
        ];
    }

    private function compileResolveTypeFunction(array|Closure $resolveFunction): string
    {
        if (is_array($resolveFunction)) {
            [$className, $methodName] = $resolveFunction;
            return Compiling::absoluteClassName($className) . '::' . $methodName;
        }

        throw new RuntimeException("Closure not yet implemented");
    }

    private function compileToTypeFunction(string $typeClassName, array $configArray): string
    {
        $className = Compiling::absoluteClassName($typeClassName);
        $config = Compiling::exportArray($configArray);

        return "static function({$this->registryNameSpace()} \${$this->typeRegistryName}) {
            return new {$className}({$config});
        }";
    }

    private function registryNameSpace(): string
    {
        return Compiling::absoluteClassName(TypeRegistry::class);
    }

    private function compileListOfType(array $types): array
    {
        return array_map(static function (mixed $type): RawPhpExpression {
            $type = is_callable($type) ? $type() : $type;
            return new RawPhpExpression((string)$type);
        }, $types);
    }

    private function compileInputFieldsToArrayDefinition(array $fieldDefinitions): string
    {
        $fields = Arrays::mapWithKeys(
            $fieldDefinitions,
            function ($_, array $definition): array {
                $code = $this->fieldCompiler->compileInputField($definition);
                return [
                    $definition['name'],
                    new RawPhpExpression("fn() => $code")
                ];
            }
        );

        return Compiling::exportArray($fields);
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
}