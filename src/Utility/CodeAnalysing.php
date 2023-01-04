<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

class CodeAnalysing
{
    public static function selfAndStaticUsages(string $code): array
    {
        $tokens = self::tokenizeCode($code);
        $usages = [];

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

    public static function usesThis(string $code): bool {
        foreach (self::tokenizeCode($code) as $token) {
            if ($token[0] === T_VARIABLE && $token[1] === '$this') {
                return true;
            }
        }
        return false;
    }

    private static function tokenizeCode(string $code): array {
        if (!str_contains($code, '<?php')) {
            $code = "<?php $code";
        }
        return token_get_all($code);
    }

    private static function isSelfOrStaticToken(string|array $token): bool {
        if ($token[0] === T_STATIC) {
            return true;
        }

        return $token[0] === T_STRING && $token[1] === 'self';
    }
}