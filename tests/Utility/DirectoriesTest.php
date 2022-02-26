<?php declare(strict_types=1);

namespace GraphQlTools\Test\Utility;

use GraphQlTools\Utility\Directories;
use PHPUnit\Framework\TestCase;

class DirectoriesTest extends TestCase
{

    public function testFileIteratorWithRegex()
    {
        $files = iterator_to_array(Directories::fileIteratorWithRegex(__DIR__ . '/../Dummies/Schema', '/\.php$/'));

        $result = array_map(fn(\SplFileInfo $info) => $info->getRealPath(), $files);
        sort($result);

        self::assertEquals([
            realpath(__DIR__ . '/../Dummies/Schema/AnimalUnion.php'),
            realpath(__DIR__ . '/../Dummies/Schema/CreateAnimalInputType.php'),
            realpath(__DIR__ . '/../Dummies/Schema/JsonScalar.php'),
            realpath(__DIR__ . '/../Dummies/Schema/LionType.php'),
            realpath(__DIR__ . '/../Dummies/Schema/MamelInterface.php'),
            realpath(__DIR__ . '/../Dummies/Schema/QueryType.php'),
            realpath(__DIR__ . '/../Dummies/Schema/TigerType.php'),
            realpath(__DIR__ . '/../Dummies/Schema/UserType.php'),
        ], $result);
    }
}
