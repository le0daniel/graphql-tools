<?php declare(strict_types=1);

namespace GraphQlTools\Apollo;

use GraphQlTools\Utility\Arrays;

final class ProtobufClass
{
    private const NAMESPACE_REGEX = '/namespace\s(?<namespace>[a-zA-Z0-9\\\\]+);$/m';
    private const CLASS_USAGE_PREFIX = '/\\\\[A-Z][a-zA-Z0-9\\\\]+/m';
    private const CLASS_ALIAS_REGEX = '/class_alias\(.*\);$/m';

    private string $content;

    public function __construct(private string $fileRealPath)
    {
        $this->content = file_get_contents($this->fileRealPath);
    }

    private static function toLines(array $lines): string
    {
        return implode(PHP_EOL, $lines);
    }

    public function prefixNamespace(string $prefix): void
    {
        $namespace = $this->declaredNameSpace();

        if (!$namespace) {
            $this->content = str_replace('<?php', implode(PHP_EOL, [
                '<?php',
                '# Generated Namespace Prefix',
                "namespace {$prefix};",
                '',
            ]), $this->content);
            return;
        }

        if (str_starts_with($namespace, $prefix)) {
            return;
        }

        $this->content = preg_replace(self::NAMESPACE_REGEX, self::toLines([
            '# Modified Namespace Prefix',
            "namespace {$prefix}\\{$namespace};",
            '',
        ]), $this->content);
    }

    public function prefixUsedClasses(string $prefix): void
    {
        preg_match_all(self::CLASS_USAGE_PREFIX, $this->content, $matches);

        $classUsages = Arrays::removeNullValues(array_map(function (string $classUsage) use ($prefix) {
            $parts = array_values(array_filter(explode('\\', $classUsage)));

            if ($parts[0] === $prefix) {
                return null;
            }

            return in_array('Google', $parts) || in_array('Internal', $parts) ? null : $classUsage;
        }, $matches[0] ?? []));

        $classUsages = array_values(array_unique($classUsages));

        foreach ($classUsages as $classUsage) {
            $escapedRegex = preg_quote($classUsage);
            $regex = "/\s{$escapedRegex}/";
            $this->content = preg_replace($regex, " \\{$prefix}{$classUsage}", $this->content);
        }
    }

    public function removeClassAliases(): void {
        $this->content = preg_replace(self::CLASS_ALIAS_REGEX, '', $this->content);
    }

    public function declaredNameSpace(): ?string
    {
        preg_match(self::NAMESPACE_REGEX, $this->content, $matches);
        return $matches['namespace'] ?? null;
    }

    public function save(): void
    {
        file_put_contents($this->fileRealPath, $this->content);
    }

}