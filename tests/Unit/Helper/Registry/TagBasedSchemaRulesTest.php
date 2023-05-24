<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper\Registry;

use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Helper\Registry\TagBasedSchemaRules;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class TagBasedSchemaRulesTest extends TestCase
{
    use ProphecyTrait;

    private function fieldWithTags(array $tags): Field {
        $prophecy = $this->prophesize(Field::class);
        $prophecy->getTags()->willReturn($tags);
        return $prophecy->reveal();
    }

    public function testIsVisibleWithNoRulesDefined()
    {
        $checker = new TagBasedSchemaRules();
        self::assertTrue($checker->isVisible($this->fieldWithTags([])));
        self::assertTrue($checker->isVisible($this->fieldWithTags(['internal'])));
    }

    public function testIsVisibleWithWhitelist()
    {
        $checker = new TagBasedSchemaRules(onlyWithTags: ['internal']);
        self::assertFalse($checker->isVisible($this->fieldWithTags([])));
        self::assertFalse($checker->isVisible($this->fieldWithTags(['public'])));
        self::assertTrue($checker->isVisible($this->fieldWithTags(['internal'])));
    }

    public function testIsBlacklisted()
    {
        $checker = new TagBasedSchemaRules(ignoreWithTags: ['internal']);
        self::assertTrue($checker->isVisible($this->fieldWithTags([])));
        self::assertTrue($checker->isVisible($this->fieldWithTags(['public'])));
        self::assertFalse($checker->isVisible($this->fieldWithTags(['internal'])));
    }

    public function testIsWithBoth()
    {
        $checker = new TagBasedSchemaRules(ignoreWithTags: ['beta'], onlyWithTags: ['internal']);
        self::assertFalse($checker->isVisible($this->fieldWithTags([])));
        self::assertFalse($checker->isVisible($this->fieldWithTags(['public'])));
        self::assertTrue($checker->isVisible($this->fieldWithTags(['internal'])));
        self::assertTrue($checker->isVisible($this->fieldWithTags(['internal', 'else'])));
        self::assertFalse($checker->isVisible($this->fieldWithTags(['internal', 'else', 'beta'])));
    }
}
