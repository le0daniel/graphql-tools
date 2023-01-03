<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\GraphQl\Fields;

use GraphQlTools\Helper\TypeTestCase;
use GraphQlTools\Test\Dummies\Schema\LionType;

class LionFieldTest extends TypeTestCase
{

    protected function typeClassName(): string
    {
        return LionType::class;
    }

    public function testFieldWithMetadataResolution(): void {
        $result = $this->field('fieldWithMeta')
            ->visit(null);
        self::assertEquals("policy is: This is my special policy", $result);
    }
}