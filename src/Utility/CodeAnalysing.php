<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

class CodeAnalysing
{
    private static function isSelfOrStaticToken(string|array $token): bool {
        if ($token[0] === T_STATIC) {
            return true;
        }

        return $token[0] === T_STRING && $token[1] === 'self';
    }

    public static function selfAndStaticUsages(string $code): array
    {
        if (!str_starts_with(trim($code), '<?php')) {
            $code = "<?php $code";
        }

        $usages = [];
        $tokens = token_get_all($code);
        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            if (!self::isSelfOrStaticToken($token)) {
                continue;
            }

            $nextToken = $tokens[$i + 1] ?? null;
            if (!$nextToken || $nextToken[0] !== T_PAAMAYIM_NEKUDOTAYIM) {
                continue;
            }

            $usages[] = $token[1] . $nextToken[1] . $tokens[$i+2][1];
            $i = $i + 2;
        }
        return $usages;
    }
}