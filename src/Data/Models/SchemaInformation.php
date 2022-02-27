<?php declare(strict_types=1);

namespace GraphQlTools\Data\Models;

/**
 * @property-read string $graphId
 * @property-read string $graphVariant
 * @property-read string $graphReference
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

    protected function getValue(string $name): mixed
    {
        return match ($name) {
            'graphReference' => "{$this->graphId}@{$this->graphVariant}",
            default => parent::getValue($name),
        };

    }

}