<?php declare(strict_types=1);

namespace GraphQlTools\Test\Utility;

use GraphQlTools\Utility\Directories;
use PHPUnit\Framework\TestCase;

class DirectoriesTest extends TestCase
{

    public function testFileIteratorWithRegex()
    {
        $files = iterator_to_array(Directories::fileIteratorWithRegex(__DIR__ . '/../Contract', '/\.php$/'));

        $result = array_map(fn(\SplFileInfo $info) => $info->getRealPath(), $files);
        sort($result);

        self::assertEquals([
            realpath(__DIR__ . '/../Contract/DataLoaderTest.php'),
        ], $result);
    }
}
