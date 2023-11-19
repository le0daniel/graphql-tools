<?php declare(strict_types=1);

namespace GraphQlTools\Data\ValueObjects\Tracing;

use GraphQL\Error\Error;
use GraphQlTools\Utility\Typing;
use GraphQL\Language\SourceLocation;

class GraphQlError
{
    /**
     * @param string $message
     * @param array<string|int>|null $path
     * @param array<GraphQlErrorLocation> $locations
     */
    public function __construct(
        public readonly string $message,
        public readonly ?array $path,
        public readonly array  $locations,
    )
    {
    }

    public static function fromGraphQlError(Error $error): GraphQlError
    {
        return new self(
            $error->getMessage(),
            $error->path ?? null,
            array_map(static fn(SourceLocation $sourceLocation) => GraphQlErrorLocation::from($sourceLocation), $error->getLocations()),
        );
    }

    public function pathKey(): string
    {
        return implode('.', $this->path ?? []);
    }
}