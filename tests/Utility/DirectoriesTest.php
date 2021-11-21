<?php declare(strict_types=1);

namespace GraphQlTools\Test\Utility;

use GraphQlTools\Utility\Directories;
use PHPUnit\Framework\TestCase;

class DirectoriesTest extends TestCase
{

    public function testFileIteratorWithRegex()
    {
        $files = iterator_to_array(Directories::fileIteratorWithRegex(__DIR__ . '/../DataLoader', '/\.php$/'));
        self::assertEquals([
            realpath(__DIR__ . '/../DataLoader/SyncDataLoaderTest.php')
        ], array_map(fn(\SplFileInfo $info) => $info->getRealPath(), $files));
    }
}
