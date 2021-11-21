<?php declare(strict_types=1);

namespace GraphQlTools\Apollo;

use GraphQlTools\Immutable\ResolverTrace;
use GraphQlTools\Utility\Arrays;
use Protobuf\Trace\Node;

final class RootNode
{
    private const RESPONSE_NAME = 'response_name';
    private const INDEX = 'index';
    private const CHILD = 'child';

    private array $rootChildren = [];

    public static function createFromResolverTrace(array $resolvers, array $errors = []): self
    {
        $instance = new self();

        /** @var ResolverTrace|array $resolver */
        foreach ($resolvers as $resolver) {
            $instance->addFromTrace(
                $resolver instanceof ResolverTrace
                    ? $resolver
                    : ResolverTrace::fromSerialized($resolver)
            );
        }
        return $instance;
    }

    private function addFromTrace(ResolverTrace $trace): void
    {
        $tree = &$this->rootChildren;
        foreach (Arrays::splice($trace->path, 0, -1) as $currentPath) {
            $isListItem = is_int($currentPath);
            $childrenExistsAtCurrentDepth = self::childrenExists($tree, $currentPath);

            // Index nodes might have to be created on the fly.
            if (!$childrenExistsAtCurrentDepth && $isListItem) {
                array_push($tree, self::createIndexNodeData($currentPath));
            }

            if (!$childrenExistsAtCurrentDepth && !$isListItem) {
                // In case the child could not be found, we return and ignore this node.
                return;
            }

            $tree = &self::moveIntoChild(
                $tree, self::findIndexOfChildrenByValue($tree, $currentPath)
            );
        }

        array_push($tree, self::createNodeData($trace));
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
            self::RESPONSE_NAME => $trace->lastPathElement,
            'resolverTrace' => $trace,
        ];
    }

    /**
     * Transforms the Data to a node
     *
     * @param array $data
     * @return Node
     */
    private static function dataToNode(array $data): Node {
        $node = new Node();

        if (isset($data[self::INDEX])) {
            $node->setIndex($data[self::INDEX]);
            return $node;
        }

        /** @var ResolverTrace $trace */
        $trace = $data['resolverTrace'];

        $node->setType($trace->returnType);
        $node->setStartTime((string)$trace->startOffset);
        $node->setEndTime((string)($trace->startOffset + $trace->duration));
        $node->setParentType($trace->parentType);
        $node->setResponseName($trace->lastPathElement);

        // ToDo: Set GraphQL errors
        // $node->setError();

        return $node;
    }

    private static function nodeHasChildren(array $node): bool {
        return isset($node[self::CHILD]) && count($node[self::CHILD]) > 0;
    }

    /** @return Node[] */
    private function childrenToNodes(array $children): array {
        $nodes = [];

        foreach ($children as $child) {
            $node = self::dataToNode($child);

            // Append the children if needed
            if (self::nodeHasChildren($child)) {
                $node->setChild($this->childrenToNodes($child[self::CHILD]));
            }

            $nodes[] = $node;
        }
        return $nodes;
    }

    public function toProtobuf(): Node {
        return (new Node())->setChild(
            $this->childrenToNodes($this->rootChildren)
        );
    }
}