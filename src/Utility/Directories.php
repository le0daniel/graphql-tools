<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use Exception;
use Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

final class Directories
{
    /**
     * @param string $directory
     * @param string $regex
     * @return Generator<SplFileInfo>
     * @throws Exception
     */
    public static function fileIteratorWithRegex(string $directory, string $regex): Generator
    {
        if (!is_dir($directory)) {
            throw new Exception("Directory `{$directory}` does not exist");
        }

        $allFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        $phpFiles = new RegexIterator($allFiles, $regex);

        /** @var SplFileInfo $file */
        foreach ($phpFiles as $file) {
            yield $file;
        }
    }

}