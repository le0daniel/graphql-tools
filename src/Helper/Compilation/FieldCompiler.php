<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Compilation;

use Closure;
use DateTimeInterface;
use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\FloatType;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;
use GraphQlTools\Data\ValueObjects\RawPhpExpression;
use GraphQlTools\Helper\ProxyResolver;
use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\Compiling;
use ReflectionClass;
use RuntimeException;

class FieldCompiler
{
    public function __construct(private readonly ClosureCompiler $compiler)
    {
    }

    public function compileField(FieldDefinition $fieldDefinition, bool $includeResolveFunction = true): string
    {
        $config = $fieldDefinition->config;
        $hasArguments = !empty($config['args'] ?? null);
        $body = Compiling::exportArray(Arrays::removeNullValues([
            'name' => $config['name'],
            'resolve' => $includeResolveFunction
                ? new RawPhpExpression($this->compileResolver($config['resolve'] ?? null))
                : null,
            'type' => new RawPhpExpression($this->compileType($config['type'])),
            'deprecationReason' => $config['deprecationReason'] ?? null,
            'removalDate' => $config['removalDate'],
            'description' => $config['description'] ?? null,
            'args' => $hasArguments ? new RawPhpExpression($this->compileArguments($config['args'])) : null,
            'tags' => $config['tags'] ?? [],
        ]));

        $className = Compiling::absoluteClassName($fieldDefinition::class);
        return "new {$className}({$body})";
    }

    public function compileInputField(array $config): string
    {
        return Compiling::exportArray(Arrays::removeNullValues([
            'name' => $config['name'],
            'type' => new RawPhpExpression($this->compileType($config['type'])),
            'deprecationReason' => $config['deprecationReason'] ?? null,
            'removalDate' => $config['removalDate'] ?? null,
            'description' => $config['description'] ?? null,
            'defaultValue' =>  $config['defaultValue'] ?? null,
        ]));
    }

    private function compileArguments(array $arguments): string
    {
        $argumentsCode = array_map($this->compileInputField(...), $arguments);
        $implodedArguments = implode(',', $argumentsCode);
        return "[{$implodedArguments}]";
    }

    private function compileType(Type|Closure $type): string
    {
        if ($type instanceof Closure) {
            return (string)$type();
        }

        $typeClassName = Compiling::absoluteClassName($type::class);
        if ($type instanceof WrappingType) {
            $property = (new ReflectionClass($type))->getProperty('wrappedType')->getValue($type);
            $next = $this->compileType($property);
            return "new {$typeClassName}({$next})";
        }

        $typeClassName = Compiling::absoluteClassName(Type::class);
        return match ($type::class) {
            IDType::class => "{$typeClassName}::id()",
            StringType::class => "{$typeClassName}::string()",
            BooleanType::class => "{$typeClassName}::boolean()",
            IntType::class => "{$typeClassName}::int()",
            FloatType::class => "{$typeClassName}::float()",
            default => throw new RuntimeException("Can not compile this type.")
        };
    }

    private function compileResolver(?ProxyResolver $resolver): ?string
    {
        $className = Compiling::absoluteClassName(ProxyResolver::class);
        if (!$resolver) {
            return "new {$className}(null)";
        }

        return $resolver->isDefaultResolveFunction()
            ? "new {$className}(null)"
            : "new {$className}({$this->compiler->compile($resolver->resolveFunction)})";
    }

}