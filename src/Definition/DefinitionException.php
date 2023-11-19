<?php declare(strict_types=1);

namespace GraphQlTools\Definition;

use Exception;
use GraphQlTools\Utility\Debugging;

final class DefinitionException extends Exception
{

    public static function from(mixed $givenType, string ...$expectedTypes): self
    {
        $expectedTypesString = implode(', ', $expectedTypes);
        $className = Debugging::typeOf($givenType);
        return new self("Expected type of {$expectedTypesString}, got {$className}");
    }

    public static function fromMissingFieldDeclaration(string $methodName, string $fieldName, ?string $message = null): self
    {
        return new self(
            "Expected definition of {$methodName} (->{$methodName}(...)), but was never called for field with name '{$fieldName}'. " .
            ($message ? $message : '')
        );
    }

}