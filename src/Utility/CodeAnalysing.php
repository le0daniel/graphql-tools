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

    public static function tokenizeCode(string $code): array {
        if (!str_contains($code, '<?php')) {
            $code = "<?php $code";
        }
        return token_get_all($code);
    }

    public static function findUsedNamespacesInFile(string $fileName): array
    {
        $tokens = token_get_all(file_get_contents($fileName));

        $use = [];
        $state = null;
        $code = '';

        foreach ($tokens as $token) {
            if ($state === null) {
                switch ($token[0]) {
                    case T_USE:
                        $state = 'use';
                        break;
                }
            }
            if ($state === 'use') {
                switch ($token[0]) {
                    case T_USE:
                        break;
                    case T_STRING:
                    case T_NAME_QUALIFIED:
                        $code .= $token[1];
                        break;
                    case ';':
                        $use[] = $code;
                        $code = '';
                        $state = null;
                        break;
                    case '(':
                        $code = '';
                        $state = null;
                        break;
                    default:
                        $code .= is_array($token) ? $token[1] : $token;
                }
            }
        }

        return $use;
    }

    private static function isSelfOrStaticToken(string|array $token): bool {
        if ($token[0] === T_STATIC) {
            return true;
        }

        return $token[0] === T_STRING && $token[1] === 'self';
    }
}