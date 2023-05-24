<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Registry;

use GraphQlTools\Contract\SchemaRules;
use GraphQlTools\Definition\Field\EnumValue;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;

final class AllVisibleSchemaRule implements SchemaRules
{

    public function isVisible(EnumValue|Field|InputField $item): bool
    {
        return true;
    }
}