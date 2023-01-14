<?php declare(strict_types=1);

namespace GraphQlTools\Apollo;

use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\Classes;

final class ProtobufClassModifier
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

    private function pregMatchAllClassUsage(): array
    {
        preg_match_all(self::CLASS_USAGE_PREFIX, $this->content, $matches);
        return $matches ?? [];
    }

    private function pregMatchContent(string $regex, ?string $key): mixed
    {
        preg_match($regex, $this->content, $matches);

        return $key
            ? ($matches[$key] ?? null)
            : $matches;
    }

    private function pregReplaceContent(string $regex, string $replace): void
    {
        $this->content = preg_replace($regex, $replace, $this->content);
    }

    private function replaceContent(string $search, string $replace): void
    {
        $this->content = str_replace($search, $replace, $this->content);
    }

    public function prefixNamespace(string $prefix): void
    {
        $namespace = $this->declaredNameSpace();

        if (!$namespace) {
            $this->replaceContent('<?php', self::toLines([
                '<?php',
                '# Generated Namespace Prefix',
                "namespace {$prefix};",
                ''
            ]));
            return;
        }

        if (str_starts_with($namespace, $prefix)) {
            return;
        }

        $this->pregReplaceContent(self::NAMESPACE_REGEX, self::toLines([
            '# Modified Namespace Prefix',
            "namespace {$prefix}\\{$namespace};",
            '',
        ]));
    }

    public function prefixUsedClasses(string $prefix): void
    {
        $matches = $this->pregMatchAllClassUsage();

        $classUsages = Arrays::removeNullValues(array_map(function (string $classUsage) use ($prefix) {
            $parts = Classes::classNameAsArray($classUsage);
            return $parts[0] === $prefix || Arrays::containsOneOf($parts, ['Google', 'Internal'])
                ? null
                : $classUsage;
        }, $matches[0] ?? []));

        $uniqueOccurrences = array_unique($classUsages);

        foreach ($uniqueOccurrences as $classUsage) {
            $escapedRegex = preg_quote($classUsage);
            $regex = "/\s{$escapedRegex}/";
            $this->pregReplaceContent($regex, " \\{$prefix}{$classUsage}");
        }
    }

    public function removeClassAliases(): void
    {
        $this->pregReplaceContent(self::CLASS_ALIAS_REGEX, '# Removed class alias');
    }

    public function declaredNameSpace(): ?string
    {
        return $this->pregMatchContent(self::NAMESPACE_REGEX, 'namespace');
    }

    public function save(): void
    {
        file_put_contents($this->fileRealPath, $this->content);
    }

}