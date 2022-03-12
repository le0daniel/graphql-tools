<?php declare(strict_types=1);

namespace GraphQlTools\Test\Fields;

use GraphQlTools\Data\Models\Holder;
use GraphQlTools\Helper\TypeTestCase;
use GraphQlTools\Test\Dummies\HolderDummy;
use GraphQlTools\Test\Dummies\Schema\TigerType;

class TigerTest extends TypeTestCase
{

    protected function typeClassName(): string
    {
        return TigerType::class;
    }

    public function testSoundField()
    {
        $result = $this->visitField('sound', ['sound' => 'true']);
        $this->assertEquals('true', $result);
    }

    public function testDeferredField()
    {
        $result = $this->visitField('deferred', ['id' => 2]);
        $this->assertEquals('My Deferred', $result);
    }

    public function testFieldWithArgs()
    {
        $this->expectVisitException("Validation failed for 'test': Failed", 'withArg', null, []);
        $this->assertEquals('success', $this->visitField('withArg', null, ['test' => 'success']));
    }

    public function testFieldWithInjections() {
        $result = $this->visitField(
            'fieldWithInjections',
            [],
            [],
            $this->contextWithMocks([HolderDummy::class => HolderDummy::create(['result' => 'result'])])
        );

        self::assertEquals('result', $result);
    }
}