<?php declare(strict_types=1);

use GraphQlTools\Apollo\ProtobufClass;
use GraphQlTools\Utility\Directories;
require_once __DIR__ . '/vendor/autoload.php';

final class GenerateProtobuf {
    const BUILD_DIRECTORY = __DIR__ . '/build';
    const NAMESPACE_REGEX = '/namespace\s+([a-zA-Z0-9\\\\]+);/';
    const PREFIX_NAMESPACE = 'Protobuf';

    public function __construct()
    {
    }

    public function execute(){
        $this->generateProtobuf();

        $files = Directories::fileIteratorWithRegex(self::BUILD_DIRECTORY, '/\.php$/');
        foreach ($files as $file) {
            $protobufClass = new ProtobufClass($file->getRealPath());
            $protobufClass->removeClassAliases();
            $protobufClass->prefixNamespace(self::PREFIX_NAMESPACE);
            $protobufClass->prefixUsedClasses(self::PREFIX_NAMESPACE);
            $protobufClass->save();
        }

    }

    private function generateProtobuf(): void {
        $files = Directories::fileIteratorWithRegex(self::BUILD_DIRECTORY, '/\.php$/');
        foreach ($files as $file) {
            unlink($file->getRealPath());
        }

        exec("protoc --php_out=build ./report.proto");

        $files = Directories::fileIteratorWithRegex(self::BUILD_DIRECTORY, '/\.php$/');
        foreach ($files as $file) {
            if (str_contains($file->getRealPath(), '_')) {
                unlink($file->getRealPath());
            }
        }
    }

}

(new GenerateProtobuf())->execute();