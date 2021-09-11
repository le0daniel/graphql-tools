<?php declare(strict_types=1);

namespace GraphQlTools\Immutable;

/**
 * @property-read string $graphId
 * @property-read string $graphVariant
 */
final class SchemaInformation extends Holder
{

    public static function from(string $graphId, string $graphVariant)
    {
        return new self([
            'graphId' => $graphId,
            'graphVariant' => $graphVariant,
        ]);
    }

}