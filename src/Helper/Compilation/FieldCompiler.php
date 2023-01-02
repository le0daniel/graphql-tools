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

        return "{$this->absoluteClassName($fieldDefinition::class)}::create([
            {$lines}
        ])";
    }

    public function compileInputField(array $config): string
    {
        $lines = implode(',' . PHP_EOL, Arrays::removeNullValues([
            "'name' => {$this->export($config['name'])}",
            "'type' => {$this->compileType($config['type'])}",
            "'deprecationReason' => {$this->export($config['deprecationReason'] ?? null)}",
            "'removalDate' => {$this->export($config['removalDate'])}",
            "'description' => {$this->export($config['description'] ?? null)}",
            isset($config['defaultValue'])
                ? "'defaultValue' => {$this->export($config['defaultValue'])}"
                : null,
        ]));
        return "[
            {$lines}
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

        if ($type instanceof WrappingType) {
            $property = (new ReflectionClass($type))->getProperty('ofType')->getValue($type);
            $next = $this->compileType($property);
            return "new {$this->absoluteClassName($type::class)}({$next})";
        }

        return match ($type::class) {
            IDType::class => "{$this->absoluteClassName(Type::class)}::id()",
            StringType::class => "{$this->absoluteClassName(Type::class)}::string()",
            BooleanType::class => "{$this->absoluteClassName(Type::class)}::boolean()",
            IntType::class => "{$this->absoluteClassName(Type::class)}::int()",
            FloatType::class => "{$this->absoluteClassName(Type::class)}::float()",
            default => throw new \RuntimeException("Can not compile this type.")
        };
    }

    private function export(mixed $variable): string
    {
        if ($variable instanceof DateTimeInterface) {
            return "\DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '{$variable->format('Y-m-d H:i:s')}')";
        }

        $value = var_export($variable, true);
        if (preg_match('/^[a-zA-Z0-9]+\\\\[a-zA-Z0-9:\\\\]+$/', $value)) {
            return '\\' . $value;
        }

        return $value;
    }

    private function absoluteClassName(string $className): string
    {
        return str_starts_with($className, '\\')
            ? $className
            : '\\' . $className;
    }

    private function compileResolver(?ProxyResolver $resolver): string
    {
        if (!$resolver) {
            return 'null';
        }
        $className = $this->absoluteClassName(ProxyResolver::class);

        return $resolver->isDefaultResolveFunction()
            ? "new {$className}(null)"
            : "new {$className}({$this->compiler->compile($resolver->resolveFunction)})";
    }

}