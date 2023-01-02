<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Compilation;

use Closure;
use Opis\Closure\ReflectionClosure;
use Opis\Closure\SerializableClosure;
use ReflectionException;
use RuntimeException;

class ClosureCompiler
{
    public function __construct(private readonly bool $onlyPureClosures = false)
    {
    }

    private function verifyBindings(ReflectionClosure $reflection): void
    {
        if ($reflection->isBindingRequired()) {
            throw new RuntimeException("Can not compile functions that require a binding! You used \$this.");
        }

        $usedVariables = array_filter(
            $reflection->getUseVariables(),
            function (mixed $value, string $key): bool {
                // Allow serialization of Middleware with bindings.
                if (in_array($key, ['destination', 'stack', 'pipe'])) {
                    return false;
                }

                if (is_scalar($value)) {
                    return false;
                }
                return true;
            },
            ARRAY_FILTER_USE_BOTH
        );

        if (count($usedVariables) > 0) {
            $renderedUsedVariables = array_map(fn(string $name) => "\${$name}", array_keys($usedVariables));
            throw new RuntimeException("Can not compile functions that use variable from outside it's scope. You used: " . implode(', ', $renderedUsedVariables));
        }
    }

    private function isPure(ReflectionClosure $reflection): bool
    {
        return empty($reflection->getUseVariables());
    }

    /**
     * @throws ReflectionException
     */
    public function compile(Closure $closure): string
    {
        $reflection = new ReflectionClosure($closure);

        if (MethodExtractor::isMethod($reflection)) {
            $methodExtractor = MethodExtractor::fromReflectionFunction($reflection);
            $fileName = $methodExtractor->toExecutableFile();
            $closure = require $fileName;
            $code = (new ReflectionClosure($closure))->getCode();
            unlink($fileName);
            return $code;
        }

        $this->verifyBindings($reflection);

        if ($this->isPure($reflection)) {
            return $reflection->getCode();
        }

        if ($this->onlyPureClosures) {
            throw new RuntimeException("Only pure closures can be serialized.");
        }

        // Uses Closure Serialization.
        $code = var_export(serialize(new SerializableClosure($closure)), true);
        return "unserialize($code)->getClosure()";
    }

}