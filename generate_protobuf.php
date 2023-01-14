<?php declare(strict_types=1);

use GraphQlTools\Apollo\ProtobufClassModifier;
use GraphQlTools\Utility\Directories;
use GraphQlTools\Utility\Process;

require_once __DIR__ . '/vendor/autoload.php';

const BUILD_DIRECTORY = __DIR__ . '/src/Protobuf';
const NAMESPACE_REGEX = '/namespace\s+([a-zA-Z0-9\\\\]+);/';
const PREFIX_NAMESPACE = 'GraphQlTools\Protobuf';

if (!file_exists(BUILD_DIRECTORY) && !is_dir(BUILD_DIRECTORY)) {
    mkdir(BUILD_DIRECTORY);
}

function generateProtobuf(): void {
    $files = Directories::fileIteratorWithRegex(BUILD_DIRECTORY, '/\.php$/');
    foreach ($files as $file) {
        unlink($file->getRealPath());
    }

    Process::mustExecute('protoc', [
        'php_out' => BUILD_DIRECTORY,
        './report.proto'
    ]);

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
    $protobufClass = new ProtobufClassModifier($file->getRealPath());
    $protobufClass->removeClassAliases();
    $protobufClass->prefixNamespace(PREFIX_NAMESPACE);
    $protobufClass->prefixUsedClasses(PREFIX_NAMESPACE);
    $protobufClass->save();
}
writeLine('Successfully generated protobuf files');