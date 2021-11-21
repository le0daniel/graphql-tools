<?php declare(strict_types=1);

use GraphQlTools\Apollo\ProtobufClass;
use GraphQlTools\Utility\Directories;
require_once __DIR__ . '/vendor/autoload.php';

const BUILD_DIRECTORY = __DIR__ . '/build';
const NAMESPACE_REGEX = '/namespace\s+([a-zA-Z0-9\\\\]+);/';
const PREFIX_NAMESPACE = 'Protobuf';

function generateProtobuf(): void {
    $files = Directories::fileIteratorWithRegex(BUILD_DIRECTORY, '/\.php$/');
    foreach ($files as $file) {
        unlink($file->getRealPath());
    }

    exec("protoc --php_out=build ./report.proto", result_code: $resultCode);
    if ($resultCode !== 0) {
        throw new RuntimeException('Error generating protobuf files using protoc.');
    }

    $files = Directories::fileIteratorWithRegex(BUILD_DIRECTORY, '/\.php$/');
    foreach ($files as $file) {
        if (str_contains($file->getRealPath(), '_')) {
            unlink($file->getRealPath());
        }
    }
}

function writeLine(string $line): void {
    echo $line . PHP_EOL;
}

writeLine('Generate new Protobuf files');
generateProtobuf();

writeLine('Modify Protobuf files');
$files = Directories::fileIteratorWithRegex(BUILD_DIRECTORY, '/\.php$/');
foreach ($files as $file) {
    $protobufClass = new ProtobufClass($file->getRealPath());
    $protobufClass->removeClassAliases();
    $protobufClass->prefixNamespace(PREFIX_NAMESPACE);
    $protobufClass->prefixUsedClasses(PREFIX_NAMESPACE);
    $protobufClass->save();
}
writeLine('Successfully generated protobuf files');