<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use GraphQL\Type\Definition\ResolveInfo;

class Fields
{
    public const METADATA_CONFIG_KEY = 'metadata';

    public static function metadataFromResolveInfo(ResolveInfo $resolveInfo): mixed {
        return $resolveInfo->fieldDefinition->config[self::METADATA_CONFIG_KEY] ?? null;
    }

}