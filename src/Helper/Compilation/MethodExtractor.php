<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Compilation;

use GraphQlTools\Utility\Classes;
use GraphQlTools\Utility\Compiling;
use Opis\Closure\ReflectionClosure;
use ReflectionFunction;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use RuntimeException;
use Throwable;

class MethodExtractor
{
    private const METHOD_NAME_REGEX = '/function\s+(?<name>[a-zA-Z0-9]+)\(/';
    private const CLOSURE_NAMESPACE = '__CompiledClosure';
    private readonly ReflectionMethod $methodReflection;

    /**
     * @throws \ReflectionException
     */
    public function __construct(private readonly string $className, private readonly string $methodName, private readonly string $declaringCode)
    {
        $this->methodReflection = new ReflectionMethod($this->className, $this->methodName);
        $this->verifyScopeUsage();
    }

    public static function fromReflectionFunction(ReflectionFunction $function): self
    {
        $code = self::getCodeFromReflection($function);
        $methodName = self::getMethodName($code);
        $className = Classes::getDeclaredClassInFile($function->getFileName());
        return new self($className, $methodName, $code);
    }

    public function toCode(): string
    {
        if ($this->isPublicStatic()) {
            return $this->absoluteClassName($this->methodReflection->getDeclaringClass()->getName()) . '::' . $this->methodName . '(...)';
        }

        $fileName = tempnam(sys_get_temp_dir(), 'closure');

        try {
            file_put_contents($fileName, $this->buildNamespacedClosure());
            $closure = require $fileName;
            return (new ReflectionClosure($closure))->getCode();
        } finally {
            if (file_exists($fileName)) {
                unlink($fileName);
            }
        }
    }

    public static function isMethod(ReflectionFunction $function): bool
    {
        try {
            Classes::getDeclaredClassInFile($function->getFileName());
            $code = self::getCodeFromReflection($function);
            return preg_match(self::METHOD_NAME_REGEX, $code) === 1;
        } catch (Throwable) {
            return false;
        }
    }

    private static function getCodeFromReflection(ReflectionFunction $function): string
    {
        $linesOfCodeInFile = explode(PHP_EOL, file_get_contents($function->getFileName()));
        $methodBodyLines = array_slice($linesOfCodeInFile, $function->getStartLine() - 1, $function->getEndLine() - ($function->getStartLine() - 1));
        return implode(PHP_EOL, $methodBodyLines);
    }

    private static function getMethodName(string $code): string
    {
        preg_match(self::METHOD_NAME_REGEX, $code, $matches);
        if (!isset($matches['name'])) {
            throw new RuntimeException("Could not get method name in code: " . $code);
        }
        return $matches['name'];
    }

    private function verifyScopeUsage(): void
    {
        $tokens = token_get_all("<?php declare(strict_types=1); {$this->declaringCode};");

        foreach ($tokens as $token) {
            if ($token[0] === T_VARIABLE && $token[1] === '$this') {
                throw new RuntimeException("Can not extract method which uses `\$this`.");
            }
        }
    }

    private function isPublicStatic(): bool
    {
        return $this->methodReflection->isStatic() && $this->methodReflection->isPublic();
    }

    private function buildNamespacedClosure(): string
    {
        $functionCode = $this->buildMethodCode();
        $usedNamespaces = $this->findUsedNamespaces();

        $namespace = $this->methodReflection->getDeclaringClass()->inNamespace()
            ? $this->methodReflection->getDeclaringClass()->getNamespaceName()
            : self::CLOSURE_NAMESPACE;

        return "<?php declare(strict_types=1);
            namespace {$namespace} {
                {$this->implodeUsedNamespaces($usedNamespaces)}
                return {$functionCode};
            }
        ";
    }

    private function buildMethodCode(): string
    {
        $parameters = implode(', ', array_map($this->parameterToString(...), $this->methodReflection->getParameters()));
        $returnType = $this->methodReflection->hasReturnType()
            ? ": " . Compiling::parameterTypeToString($this->methodReflection->getReturnType())
            : '';
        $openBracketPosition = strpos($this->declaringCode, '{');
        if (!$openBracketPosition) {
            throw new RuntimeException("Could not find method open bracket.");
        }

        $replacements = [
            'self::' => $this->absoluteClassName($this->methodReflection->getDeclaringClass()->name) . '::',
            'static::' => $this->absoluteClassName($this->className) . '::'
        ];

        $codeWithReplacedSelfAndStatic = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $this->declaringCode
        );

        $codeFromStartingBracket = substr($codeWithReplacedSelfAndStatic, $openBracketPosition + 1);

        return "static function ({$parameters}){$returnType} {" . $codeFromStartingBracket;
    }

    private function implodeUsedNamespaces(array $lines): string
    {
        return implode(PHP_EOL, array_map(fn(string $namespace): string => "use {$namespace};", $lines));
    }

    private function export(mixed $variable): string
    {
        return Compiling::exportVariable($variable);
    }

    private function absoluteClassName(string $classname): string
    {
        return Compiling::absoluteClassName($classname);
    }

    /**
     * @throws \ReflectionException
     */
    private function findUsedNamespaces(): array
    {
        $tokens = token_get_all(file_get_contents($this->methodReflection->getFileName()));

        $use = [];
        $state = null;
        $code = '';

        foreach ($tokens as $token) {
            if ($state === null) {
                switch ($token[0]) {
                    case T_USE:
                        $state = 'use';
                        break;
                }
            }
            if ($state === 'use') {
                switch ($token[0]) {
                    case T_USE:
                        break;
                    case T_STRING:
                    case T_NAME_QUALIFIED:
                        $code .= $token[1];
                        break;
                    case ';':
                        $use[] = $code;
                        $code = '';
                        $state = null;
                        break;
                    case '(':
                        $code = '';
                        $state = null;
                        break;
                    default:
                        $code .= is_array($token) ? $token[1] : $token;
                }
            }
        }

        return $use;
    }

    private function parameterToString(ReflectionParameter $parameter): string
    {
        $parameterNameAndDefaultValue = $parameter->isDefaultValueAvailable()
            ? "\${$parameter->name} = {$this->export($parameter->getDefaultValue())}"
            : '$' . $parameter->name;

        if (!$parameter->getType()) {
            return $parameterNameAndDefaultValue;
        }

        $type = $parameter->getType();
        return Compiling::parameterTypeToString($type) . " {$parameterNameAndDefaultValue}";
    }

}