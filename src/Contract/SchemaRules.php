<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

use GraphQlTools\Definition\Field\EnumValue;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;

interface SchemaRules
{
    public function isVisible(Field|InputField|EnumValue $item): bool;
}