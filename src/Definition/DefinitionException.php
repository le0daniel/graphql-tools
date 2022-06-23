<?php declare(strict_types=1);

namespace GraphQlTools\Definition;

final class DefinitionException extends \Exception
{

    public static function from(mixed $givenType, string ...$expectedTypes): self
    {
        $expectedTypesString = implode(', ', $expectedTypes);
        $className = self::serializeGivenType($givenType);
        return new self("Expected type of {$expectedTypesString}, got {$className}");
    }

    private static function serializeGivenType(mixed $givenType): string {
        if (is_string($givenType)) {
            $length = strlen($givenType);
            return class_exists($givenType)
                ? "class-string({$givenType})"
                : "string({$length} '{$givenType}')";
        }

        return is_object($givenType) ? get_class($givenType) : gettype($givenType);
    }

    public static function fromMissingFieldDeclaration(string $methodName, string $fieldName, ?string $message = null)
    {
        return new self(
            "Expected definition of {$methodName} (->{$methodName}(...)), but was never called for field with name '{$fieldName}'. " .
            ($message ? $message : '')
        );
    }

}