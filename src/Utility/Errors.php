<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use GraphQL\Error\Error;

final class Errors
{

    /**
     * @param array<Error> $errors
     * @param array $path
     * @return array<Error>
     */
    public static function filterByPath(array $errors, array $path): array
    {
        $pathAsString = implode('.', $path);
        $filtered = [];
        foreach ($errors as $error) {
            $errorPath = implode('.', $error->path);
            if (str_starts_with($errorPath, $pathAsString)) {
                $filtered[] = $error;
            }
        }
        return $filtered;
    }

}