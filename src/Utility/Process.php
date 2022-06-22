<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use RuntimeException;

class Process
{

    /**
     * @param string $command
     * @param array<string|int, string> $arguments
     * @param array<int> $allowedReturnCodes
     * @return array<string>
     */
    public static function mustExecute(string $command, array $arguments = [], array $allowedReturnCodes = []): array
    {
        $escapedArguments = [];
        foreach ($arguments as $name => $argument) {
            $escapedArguments[] = self::buildArgument($name, $argument);
        }

        $fullCommand = $command . " " . implode(' ', $escapedArguments);

        $output = [];
        exec($fullCommand, $output, $resultCode);

        if ($resultCode !== 0 && !in_array($resultCode, $allowedReturnCodes, true)) {
            throw new RuntimeException("Failed to execute `{$command}` with return code {$resultCode}: " . implode(PHP_EOL, $output));
        }

        return $output;
    }

    private static function buildArgument(string|int $name, string $argument): string
    {
        $isPositionalArgument = is_int($name);
        if ($isPositionalArgument) {
            return escapeshellarg($argument);
        }

        if (!$argument) {
            return "--{$name}";
        }

        $escapedArgument = escapeshellarg($argument);
        return "--{$name}=$escapedArgument";
    }

}