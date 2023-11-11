<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Registry;

use GraphQlTools\Contract\SchemaRules;
use GraphQlTools\Definition\Field\EnumValue;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;

final readonly class TagBasedSchemaRules implements SchemaRules
{
    private bool $blacklistCheckRequired;
    private bool $whitelistCheckRequired;

    public function __construct(
        private array $ignoreWithTags = [],
        private array $onlyWithTags = [],
    )
    {
        $this->blacklistCheckRequired = !empty($this->ignoreWithTags);
        $this->whitelistCheckRequired = !empty($this->onlyWithTags);
    }

    private function isBlacklisted(array $tags): bool {
        $blacklistedTagsCount = count(array_intersect($tags, $this->ignoreWithTags));
        return $blacklistedTagsCount > 0;
    }

    private function isWhitelisted(array $tags): bool {
        $whitelistedCount = count(array_intersect($tags, $this->onlyWithTags));
        return $whitelistedCount > 0;
    }

    public function isVisible(EnumValue|Field|InputField $item): bool
    {
        $tags = $item->getTags();

        if ($this->blacklistCheckRequired && $this->isBlacklisted($tags)) {
            return false;
        }

        if (!$this->whitelistCheckRequired) {
            return true;
        }

        return $this->isWhitelisted($tags);
    }
}