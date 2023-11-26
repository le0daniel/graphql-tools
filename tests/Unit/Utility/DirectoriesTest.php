<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Utility;

use GraphQlTools\Utility\Directories;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

class DirectoriesTest extends TestCase
{

    public function testFileIteratorWithRegex()
    {
        $directoryToIterate = realpath(__DIR__ . '/../Helper');

        $files = iterator_to_array(Directories::fileIteratorWithRegex($directoryToIterate, '/\.php$/'));

        $result = array_map(fn(SplFileInfo $info) => str_replace($directoryToIterate . '/', '', $info->getRealPath()), $files);
        sort($result);

        self::assertEquals([
            'ContextTest.php',
            'DataLoader/CachedDataLoaderTest.php',
            'DataLoaderTest.php',
            'ExtensionManagerTest.php',
            'MiddlewareTest.php',
            'OperationContextTest.php',
            'ProxyResolverTest.php',
            'QueryExecutorTest.php',
            'Registry/FactoryTypeRegistryTest.php',
            'Registry/SchemaRegistrySchemaTest.php',
            'Registry/TagBasedSchemaRulesTest.php',
            'Results/GraphQlResultTest.php',
            'Validation/QueryComplexityRuleTest.php'
        ], $result);

        foreach ($result as $relativePath) {
            self::assertTrue(file_exists("{$directoryToIterate}/{$relativePath}"));
        }
    }
}
