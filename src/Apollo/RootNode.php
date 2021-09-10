<?php declare(strict_types=1);

namespace GraphQlTools\Apollo;

use GraphQlTools\Immutable\ResolverTrace;
use GraphQlTools\Utility\Arrays;
use JsonSerializable;

final class RootNode implements JsonSerializable
{
    private const RESPONSE_NAME = 'response_name';
    private const INDEX = 'index';
    private const CHILD = 'child';

    private array $rootChildren = [];

    public static function createFromResolverTrace(array $resolvers): self
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
                array_push($tree, self::createIndexNode($currentPath));
            }

            if (!$childrenExistsAtCurrentDepth && !$isListItem) {
                // In case the child could not be found, we return and ignore this node.
                return;
            }

            $tree = &self::moveIntoChild(
                $tree, self::findIndexOfChildrenByValue($tree, $currentPath)
            );
        }

        array_push($tree, self::createNode($trace));
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

    private function createIndexNode(int $index): array
    {
        return [self::INDEX => $index, self::CHILD => []];
    }

    private function createNode(ResolverTrace $trace): array
    {
        return [
            self::RESPONSE_NAME => $trace->lastPathElement,
            'type' => $trace->returnType,
            'start_time' => (string)$trace->startOffset,
            'end_time' => (string)($trace->startOffset + $trace->duration)
        ];
    }

    public function jsonSerialize(): array
    {
        return [
            self::CHILD => $this->rootChildren
        ];
    }
}