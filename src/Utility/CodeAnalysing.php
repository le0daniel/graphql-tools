<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

class CodeAnalysing
{
    private static array|null $variable;

    private const SELF_OR_STATIC_USAGE_IN_CODE = '/(self|static)::\$?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/m';

    public static function selfAndStaticUsages(string $code): array {
        // TODO: Improve and use tokenizer
        preg_match_all(self::SELF_OR_STATIC_USAGE_IN_CODE, $code, $matches, PREG_SET_ORDER);
        return empty($matches)
            ? []
            : array_map(fn(array $match) => $match[0], $matches);
    }
}