<?php declare(strict_types=1);

namespace GraphQlTools\Data\Models;

use GraphQL\Language\SourceLocation;

/**
 * @property-read int $line
 * @property-read int $column
 */
class GraphQlErrorLocation extends Holder
{

    public static function from(SourceLocation $sourceLocation) {
        return new self([
            'line' => $sourceLocation->line,
            'column' => $sourceLocation->column,
        ]);
    }

}