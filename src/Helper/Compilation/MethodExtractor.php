<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Compilation;

use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\Classes;
use GraphQlTools\Utility\CodeAnalysing;
use GraphQlTools\Utility\Compiling;
use GraphQlTools\Utility\Reflections;
use Opis\Closure\ReflectionClosure;
use ReflectionFunction;
use ReflectionMethod;
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
            $className = Compiling::absoluteClassName($this->methodReflection->getDeclaringClass()->getName());
            return "{$className}::{$this->methodName}(...)";
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
        $functionCode = $this->buildExtractedFunctionCode();
        $usedNamespaces = Reflections::findUsedNamespacesInDeclaringClass($this->methodReflection->getFileName());

        $namespace = $this->methodReflection->getDeclaringClass()->inNamespace()
            ? $this->methodReflection->getDeclaringClass()->getNamespaceName()
            : self::CLOSURE_NAMESPACE;

        return "<?php declare(strict_types=1);
            namespace {$namespace} {
                {$this->combineUsedNamespaces($usedNamespaces)}
                return {$functionCode};
            }
        ";
    }

    private function replaceSelfAndStaticUsageInCode(): string {
        $usages = array_unique(CodeAnalysing::selfAndStaticUsages($this->declaringCode));
        if (empty($usages)) {
            return $this->declaringCode;
        }

        $selfReplacement = Compiling::absoluteClassName($this->methodReflection->getDeclaringClass()->name);
        $staticReplacement = Compiling::absoluteClassName($this->className);

        $replacements = Arrays::mapWithKeys($usages, function($_, string $usage) use ($selfReplacement, $staticReplacement) {
            $replacement = str_starts_with($usage, 'self')
                ? $selfReplacement . substr($usage, 4)
                : $staticReplacement . substr($usage, 6);
            return [$usage => $replacement];
        });

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $this->declaringCode
        );
    }

    private function buildExtractedFunctionCode(): string
    {
        $parameters = Compiling::parametersToString(... $this->methodReflection->getParameters());
        $returnType = $this->methodReflection->hasReturnType()
            ? ": " . Compiling::reflectionTypeToString($this->methodReflection->getReturnType())
            : '';

        $code = $this->replaceSelfAndStaticUsageInCode();
        $openBracketPosition = strpos($code, '{');
        $codeFromStartingBracket = substr($code, $openBracketPosition + 1);
        return "static function ({$parameters}){$returnType} {" . $codeFromStartingBracket;
    }

    private function combineUsedNamespaces(array $lines): string
    {
        return implode(PHP_EOL, array_map(fn(string $namespace): string => "use {$namespace};", $lines));
    }
}