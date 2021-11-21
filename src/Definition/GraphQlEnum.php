<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\EnumType;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Utility\Classes;

abstract class GraphQlEnum extends EnumType {
    private const CLASS_POSTFIX = 'Scalar';
    use HasDescription;

    public function __construct(){
        parent::__construct(
            [
                'name' => static::typeName(),
                'description' => $this->description(),
                'values' => $this->values(),
            ]
        );
    }

    /**
     * Return a key value array or a serial array containing
     * either the key and the internal value or the keys only.
     *
     * @return array
     */
    abstract protected function values(): array;

    public static function typeName(): string {
        $typeName = Classes::baseName(static::class);
        return str_ends_with($typeName, self::CLASS_POSTFIX)
            ? substr($typeName, 0, -strlen(self::CLASS_POSTFIX))
            : $typeName;
    }

}
