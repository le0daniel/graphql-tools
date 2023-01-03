<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\GraphQl\Fields;

use GraphQlTools\Helper\TypeTestCase;
use GraphQlTools\Test\Dummies\Schema\TigerType;
use Throwable;

class TigerTest extends TypeTestCase
{

    protected function typeClassName(): string
    {
        return TigerType::class;
    }

    public function testSoundField()
    {
        $result = $this->field('sound')->visit(['sound' => 'true']);
        $this->assertEquals('true', $result);
    }

    public function testDeferredField()
    {
        $result = $this->field('deferred')
            ->mockedDataloader('test', fn() => [
                2 => 'My Deferred',
                3 => 'Second Deferred'
            ])
            ->visit(['id' => 2]);
        $this->assertEquals('My Deferred', $result);
    }
}