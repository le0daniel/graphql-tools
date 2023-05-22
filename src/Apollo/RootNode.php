<?php declare(strict_types=1);

namespace GraphQlTools\Apollo;

use GraphQlTools\Data\ValueObjects\Tracing\GraphQlErrorLocation;
use GraphQlTools\Data\ValueObjects\Tracing\ResolverTrace;
use GraphQlTools\Data\ValueObjects\Tracing\GraphQlError;
use GraphQlTools\Protobuf\Trace\Error;
use GraphQlTools\Protobuf\Trace\Location;
use GraphQlTools\Protobuf\Trace\Node;
use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\Typing;

final class RootNode
{
    private const RESPONSE_NAME = 'response_name';
    private const INDEX = 'index';
    private const CHILD = 'child';
    private array $rootChildren = [];

    /** @var array<string, GraphQlError> */
    private readonly array $errorIndex;

    public function __construct(array $resolverTraces, array $errors)
    {
        Typing::verifyListOfType(ResolverTrace::class, $resolverTraces);
        Typing::verifyListOfType(GraphQlError::class, $errors);

        $this->errorIndex = Arrays::mapWithKeys($errors, static function($index, GraphQlError $error): array {
            return [$error->pathKey(), $error];
        });

        /** @var ResolverTrace $resolver */
        foreach ($resolverTraces as $resolver) {
            $this->addFromTrace($resolver);
        }
    }

    private function addFromTrace(ResolverTrace $trace): void
    {
        $tree = &$this->rootChildren;
        foreach (Arrays::splice($trace->path, 0, -1) as $currentPath) {
            $isListItem = is_int($currentPath);
            $childrenExistsAtCurrentDepth = self::childrenExists($tree, $currentPath);

            // Index nodes might have to be created on the fly.
            if (!$childrenExistsAtCurrentDepth && $isListItem) {
                $tree[] = self::createIndexNodeData($currentPath);
            }

            if (!$childrenExistsAtCurrentDepth && !$isListItem) {
                // In case the child could not be found, we return and ignore this node.
                return;
            }

            $tree = &self::moveIntoChild(
                $tree, self::findIndexOfChildrenByValue($tree, $currentPath)
            );
        }

        $tree[] = self::createNodeData($trace);
    }

    private static function childrenExists(&$tree, string|int $valueToSearch): bool
    {
        return self::findIndexOfChildrenByValue($tree, $valueToSearch) !== null;
    }

    private static function findIndexOfChildrenByValue(&$tree, string|int $valueToSearch): int|null
    {
        $keyToSearchIn = is_int($valueToSearch) ? self::INDEX : self::RESPONSE_NAME;

        foreach ($tree as $index => $item) {
            if (!isset($item[$keyToSearchIn])) {
                continue;
            }

            if ($item[$keyToSearchIn] === $valueToSearch) {
                return $index;
            }
        }
        return null;
    }

    private static function &moveIntoChild(&$tree, int $index): array
    {
        // First, lets move into item with index
        $tree = &$tree[$index];

        if (!isset($tree[self::CHILD])) {
            $tree[self::CHILD] = [];
        }

        $tree = &$tree[self::CHILD];
        return $tree;
    }

    private function createIndexNodeData(int $index): array
    {
        return [
            self::INDEX => $index,
            self::CHILD => [],
        ];
    }

    private function createNodeData(ResolverTrace $trace): array
    {
        return [
            self::RESPONSE_NAME => $trace->lastPathElement(),
            'resolverTrace' => $trace,
        ];
    }

    /**
     * Transforms the Data to a node
     *
     * @param array $data
     * @return Node
     */
    private function dataToNode(array $data): Node
    {
        $node = new Node();

        if (isset($data[self::INDEX])) {
            $node->setIndex($data[self::INDEX]);
            return $node;
        }

        /** @var ResolverTrace $fieldTrace */
        $fieldTrace = $data['resolverTrace'];
        Typing::verifyOfType(ResolverTrace::class, $fieldTrace);

        $node->setType($fieldTrace->returnType);
        $node->setStartTime((string)$fieldTrace->startOffsetInNanoseconds);
        $node->setEndTime((string)($fieldTrace->startOffsetInNanoseconds + $fieldTrace->durationInNanoseconds));
        $node->setParentType($fieldTrace->parentType);
        $node->setResponseName($fieldTrace->lastPathElement());

        /** @var GraphQlError[] $errors */
        $errors = $this->errorIndex[$fieldTrace->pathKey()] ?? [];
        if (!empty($errors)) {
            $node->setError(array_map(function(GraphQlError $error): Error {
                $protobufError = new Error();
                $protobufError->setLocation(array_map(function(GraphQlErrorLocation $location): Location {
                    $protobufLocation = new Location();
                    $protobufLocation->setColumn($location->column);
                    $protobufLocation->setLine($location->line);
                    return $protobufLocation;
                }, $error->locations));
                $protobufError->setMessage($error->message);
                return $protobufError;
            }, $errors));
        }

        return $node;
    }

    private static function nodeHasChildren(array $node): bool
    {
        return isset($node[self::CHILD]) && count($node[self::CHILD]) > 0;
    }

    /** @return Node[] */
    private function childrenToNodes(array $children): array
    {
        $nodes = [];

        foreach ($children as $child) {
            $node = $this->dataToNode($child);

            // Append the children if needed
            if (self::nodeHasChildren($child)) {
                $node->setChild($this->childrenToNodes($child[self::CHILD]));
            }

            $nodes[] = $node;
        }
        return $nodes;
    }

    public function toProtobuf(): Node
    {
        return (new Node())->setChild(
            $this->childrenToNodes($this->rootChildren)
        );
    }
}