<?php declare(strict_types=1);

namespace GraphQlTools\Data\Models;

use GraphQL\Error\Error;
use \Protobuf\Trace\Error as ProtobufError;
use GraphQL\Language\SourceLocation;
use Protobuf\Trace\Location;

/**
 * @property-read string $message
 * @property-read string[]|null $path
 * @property-read string $pathKey
 * @property-read GraphQlErrorLocation[] $locations
 */
class GraphQlError extends Holder
{

    public static function fromGraphQlError(Error $error)
    {
        return new self([
            'message' => $error->getMessage(),
            'path' => $error->path ?? null,
            'locations' => array_map(static fn(SourceLocation $sourceLocation) => GraphQlErrorLocation::from($sourceLocation), $error->getLocations()),
        ]);
    }

    protected function getValue(string $name): mixed
    {
        return match ($name) {
            'pathKey' => implode('.', $this->path ?? []),
            default => parent::getValue($name)
        };
    }

    public function toProtobufError(): ProtobufError {
        $error = new ProtobufError();
        $locations = array_map(static function(GraphQlErrorLocation $errorLocation): Location {
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