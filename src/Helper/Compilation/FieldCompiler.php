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
use GraphQlTools\Helper\ProxyResolver;
use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\Compiling;
use ReflectionClass;

class FieldCompiler
{
    public function __construct(private readonly ClosureCompiler $compiler)
    {
    }

    public function compileField(FieldDefinition $fieldDefinition, bool $includeResolveFunction = true): string
    {
        $config = $fieldDefinition->config;
        $lines = implode(',' . PHP_EOL, Arrays::removeNullValues([
            "'name' => {$this->export($config['name'])}",
            $includeResolveFunction
                ? "'resolve' => {$this->compileResolver($config['resolve'] ?? null)}"
                : null,
            "'type' => {$this->compileType($config['type'])}",
            "'deprecationReason' => {$this->export($config['deprecationReason'] ?? null)}",
            "'removalDate' => {$this->export($config['removalDate'])}",
            "'description' => {$this->export($config['description'] ?? null)}",
            !empty($config['args'] ?? null)
                ? "'args' => {$this->compileArguments($config['args'])}"
                : null,
        ]));

        $className = Compiling::absoluteClassName($fieldDefinition::class);
        return "{$className}::create([
            {$lines}
        ])";
    }

    public function compileInputField(array $config): string
    {
        $lines = Arrays::removeNullValues([
            "'name' => {$this->export($config['name'])}",
            "'type' => {$this->compileType($config['type'])}",
            "'deprecationReason' => {$this->export($config['deprecationReason'] ?? null)}",
            "'removalDate' => {$this->export($config['removalDate'])}",
            "'description' => {$this->export($config['description'] ?? null)}",
            isset($config['defaultValue'])
                ? "'defaultValue' => {$this->export($config['defaultValue'])}"
                : null,
        ]);
        $implodedLines = implode(',' . PHP_EOL, $lines);

        return "[
            {$implodedLines}
        ]";
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
            return (string) $type();
        }

        $typeClassName = Compiling::absoluteClassName($type::class);
        if ($type instanceof WrappingType) {
            $property = (new ReflectionClass($type))->getProperty('ofType')->getValue($type);
            $next = $this->compileType($property);
            return "new {$typeClassName}({$next})";
        }

        return match ($type::class) {
            IDType::class => "{$typeClassName}::id()",
            StringType::class => "{$typeClassName}::string()",
            BooleanType::class => "{$typeClassName}::boolean()",
            IntType::class => "{$typeClassName}::int()",
            FloatType::class => "{$typeClassName}::float()",
            default => throw new \RuntimeException("Can not compile this type.")
        };
    }

    private function export(mixed $variable): string
    {
        return Compiling::exportVariable($variable);
    }

    private function compileResolver(?ProxyResolver $resolver): string
    {
        if (!$resolver) {
            return 'null';
        }

        $className = Compiling::absoluteClassName(ProxyResolver::class);

        return $resolver->isDefaultResolveFunction()
            ? "new {$className}(null)"
            : "new {$className}({$this->compiler->compile($resolver->resolveFunction)})";
    }

}