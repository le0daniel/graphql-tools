<?php declare(strict_types=1);

namespace GraphQlTools\Test\Apollo;

use GraphQlTools\Apollo\RootNode;
use PHPUnit\Framework\TestCase;

class RootNodeTest extends TestCase
{
    private const TRACE_FILE = __DIR__ . '/../files/trace.json';
    private const EXPECTED_ROOT_NODE = __DIR__ . '/../files/expected-root-node.json';

    public function testJsonSerialize()
    {
        $trace = json_decode(file_get_contents(__DIR__ . '/../files/trace.json'), true, flags: JSON_THROW_ON_ERROR);
        $rootNode = RootNode::createFromResolverTrace($trace['execution']['resolvers']);

        self::assertEquals(file_get_contents(self::EXPECTED_ROOT_NODE), json_encode($rootNode));
    }
}
