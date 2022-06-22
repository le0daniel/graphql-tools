<?php declare(strict_types=1);

namespace GraphQlTools\Data\Models;

use GraphQL\Error\Error;
use GraphQlTools\Utility\Lists;
use \Protobuf\Trace\Error as ProtobufError;
use GraphQL\Language\SourceLocation;
use Protobuf\Trace\Location;

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
        Lists::verifyOfType(GraphQlErrorLocation::class, $this->locations);
    }

    public static function fromGraphQlError(Error $error)
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

    public function toProtobufError(): ProtobufError
    {
        $error = new ProtobufError();
        $locations = array_map(static function (GraphQlErrorLocation $errorLocation): Location {
            $protoBufLocation = new Location();
            $protoBufLocation->setColumn($errorLocation->column);
            $protoBufLocation->setLine($errorLocation->line);
            return $protoBufLocation;
        }, $this->locations);

        $error->setLocation($locations);
        $error->setMessage($this->message);
        return $error;
    }

}