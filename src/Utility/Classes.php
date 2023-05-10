<?php

declare(strict_types=1);

namespace GraphQlTools\Utility;

use RuntimeException;

final class Classes
{

    public static function isClassName(string $possibleClassName): bool
    {
        return str_contains($possibleClassName, '\\');
    }

    public static function baseName(string $className): string
    {
        $parts = explode('\\', $className);
        return end($parts);
    }

    /**
     * @param string $file
     * @return class-string|null
     */
    public static function getDeclaredClassInFile(string $file): ?string
    {
        /** @var class-string[] $classes */
        $classes = [];
        $content = file_get_contents($file);
        if (!$content) {
            throw new RuntimeException("Failed to read file: '{$file}'");
        }

        $tokens = token_get_all($content);
        $namespace = '';

        for ($index = 0; isset($tokens[$index]); $index++) {
            if (!isset($tokens[$index][0])) {
                continue;
            }

            if (T_NAMESPACE === $tokens[$index][0]) {
                $index += 2; // Skip namespace keyword and whitespace
                while (isset($tokens[$index]) && is_array($tokens[$index])) {
                    $namespace .= $tokens[$index++][1];
                }
            }
            if (T_CLASS === $tokens[$index][0] && T_WHITESPACE === $tokens[$index + 1][0] && T_STRING === $tokens[$index + 2][0]) {
                $index += 2; // Skip class keyword and whitespace

                /** @var class-string $classString */
                $classString = $namespace . '\\' . $tokens[$index][1];
                $classes[] = $classString;

                # break if you have one class per file (psr-4 compliant)
                # otherwise you'll need to handle class constants (Foo::class)
                break;
            }
        }

        if (empty($classes)) {
            return null;
        }

        if (count($classes) !== 1) {
            throw new RuntimeException("None or more than one class defined in file: {$file}");
        }

        return $classes[0] ?? null;
    }

    /**
     * @param string $fullyQualifiedClassName
     * @return array<string>
     */
    public static function classNameAsArray(string $fullyQualifiedClassName): array
    {
        return array_values(array_filter(explode('\\', $fullyQualifiedClassName)));
    }

}
