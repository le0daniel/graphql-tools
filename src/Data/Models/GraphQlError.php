<?php declare(strict_types=1);

namespace GraphQlTools\Data\Models;

use GraphQL\Error\Error;

/**
 * @property-read string $message
 * @property-read string[]|null
 */
class GraphQlError extends Holder
{

    public static function fromGraphQlError(Error $error) {
        return new self([
            'message' => $error->getMessage(),
            'path' => $error->path ?? null
        ]);
    }

}