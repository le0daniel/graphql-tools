<?php declare(strict_types=1);


use GraphQlTools\Utility\CodeAnalysing;

require __DIR__ . '/vendor/autoload.php';


$code = 'function (string $input) {
    return self::$MY_VARIABLE . static::STRING . $input . "self::something";
}';

var_dump(CodeAnalysing::selfAndStaticUsages($code));
