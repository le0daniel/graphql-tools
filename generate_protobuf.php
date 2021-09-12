<?php declare(strict_types=1);

final class GenerateProtobuf {
    const PROTOBUF_SOURCE_FILE = __DIR__ . '/report.proto';
    const BUILD_DIRECTORY = __DIR__ . '/build';
    const NAMESPACE_REGEX = '/namespace\s+([a-zA-Z0-9\\\\]+);/';
    const CLASSNAME_REGEX = '/class\s+([a-zA-Z0-9\\\\]+)\s+/';
    const CLASS_ALIAS_REGEX = '/class_alias\(.*\);/';
    const PREFIX_NAMESPACE = 'Protobuf';

    private array $classNames = [];

    public function __construct(){}

    public function execute(){
        $this->generateProtobuf();

        $files = $this->loadAllFiles();
        foreach ($files as $file) {
            if (str_contains($file->getBasename(), '_')) {
                unlink($file->getRealPath());
                continue;
            }
            $this->loadNamespaceMap($file->getRealPath());
        }

        foreach ($files as $file) {
            if (file_exists($file->getRealPath())) {
                $this->namespaceFile($file->getRealPath());
            }
        }
    }

    private function loadNamespaceMap(string $filepath): void {
        if (!file_exists($filepath)) {
            return;
        }

        $content = file_get_contents($filepath);
        $classnameParts = [];

        // Load Name space
        if (preg_match(self::NAMESPACE_REGEX, $content, $matches)) {
            $classnameParts[] = $matches[1];
        }

        if (preg_match(self::CLASSNAME_REGEX, $content, $classnames)) {
            $classnameParts[] = $classnames[1];
        }

        $this->classNames[] = '\\' . implode('\\', $classnameParts);
    }

    private function generateProtobuf(): void {}

    /**
     * @return SplFileInfo[]
     */
    private function loadAllFiles(): array {
        $dir = new RecursiveDirectoryIterator(self::BUILD_DIRECTORY);
        $ite = new RecursiveIteratorIterator($dir);
        //$files = new RegexIterator($ite, '/*.\.php$/', RegexIterator::GET_MATCH);

        $fileList = [];

        /** @var SplFileInfo $file */
        foreach($ite as $file) {
            if ($file->getExtension() === 'php') {
                $fileList[] = $file;
            }
        }

        return $fileList;
    }

    private function isNamespaced(string $content): bool {
        return preg_match(self::NAMESPACE_REGEX, $content, $matches) !== false;
    }

    private function namespaceFile(string $filepath) {
        $content = file_get_contents($filepath);

        // Remove class aliases
        if (preg_match(self::CLASS_ALIAS_REGEX, $content) !== false) {
            $content = preg_replace(self::CLASS_ALIAS_REGEX, '', $content);
        }

        // Replace Namespace
        if ($this->isNamespaced($content)) {
            $content = preg_replace('/namespace\s+/', 'namespace ' .self::PREFIX_NAMESPACE. '\\', $content, 1);
        } else {
            $content = str_replace('<?php', implode(PHP_EOL, [
                '<?php',
                'namespace ' .self::PREFIX_NAMESPACE. ';'
            ]), $content);
        }

        // Replace all namespaced classnames
        foreach ($this->classNames as $className) {
            $content = str_replace($className, '\\' .self::PREFIX_NAMESPACE. $className, $content);
        }

        $newFileName = str_replace('.php', 'New.php', $filepath);
        file_put_contents($newFileName, $content);

        // if (preg_match(self::NAMESPACE_REGEX, $content, $matches) !== false) {
        //     //var_dump($matches);
        // }
    }

}

(new GenerateProtobuf)->execute();