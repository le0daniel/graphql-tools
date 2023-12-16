<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper;

use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Helper\ExecutionManager;
use GraphQlTools\Helper\Extensions;
use GraphQlTools\Helper\OperationContext;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

class ExecutionManagerTest extends TestCase
{

    public function testStart(): void {
        $manager = new ExecutionManager();
        self::assertEquals(0, $manager->getCurrentExecution());
        $manager->start();
        self::assertEquals(1, $manager->getCurrentExecution());
        $duration = $manager->stop();
        self::assertTrue($duration > 1);

        self::assertEquals(1, $manager->getCurrentExecution());
        $manager->start();
        self::assertEquals(2, $manager->getCurrentExecution());
    }

    public function testCanNotStopManagerWhenNotRunning(): void {
        $manager = new ExecutionManager();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Can not stop when not running.");
        $manager->stop();
    }

    public function testCanNotStartManagerWhenRunning(): void {
        $manager = new ExecutionManager();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Is already running.");
        $manager->start();
        $manager->start();
    }

    public function testCanDefer(): void {
        $manager = new ExecutionManager(3);
        for ($i = 0; $i < 2; $i++) {
            $manager->start();
            $manager->stop();
            self::assertTrue($manager->canExecuteAgain());
        }

        $manager->start();
        // After 2 runs, as there is a max of 3 runs, it's not possible to further defer.
        self::assertFalse($manager->canExecuteAgain());
        self::assertEquals(3, $manager->getCurrentExecution());
        $manager->stop();
    }

    public function testDefer(): void {
        $manager = new ExecutionManager(3);
        self::assertFalse($manager->hasDeferred());
        self::assertEmpty($manager->getAllDeferred());

        self::assertFalse($manager->isDeferred(['test1']));
        self::assertFalse($manager->isDeferred(['test2']));

        $manager->addDefer(['test1'], 'my-label1', 'data1');
        $manager->addDefer(['test2'], 'my-label2', 'data2');

        self::assertTrue($manager->isDeferred(['test1']));
        self::assertTrue($manager->isDeferred(['test2']));

        self::assertTrue($manager->hasDeferred());
        self::assertEquals([[['test1'], 'my-label1'], [['test2'], 'my-label2']], $manager->getAllDeferred());

        self::assertEquals('data1', $manager->popDeferred(['test1']));
        self::assertEquals([[['test2'], 'my-label2']], $manager->getAllDeferred());
        self::assertTrue($manager->hasDeferred());
        self::assertFalse($manager->isDeferred(['test1']));
        self::assertTrue($manager->isDeferred(['test2']));

        self::assertEquals('data2', $manager->popDeferred(['test2']));
        self::assertFalse($manager->hasDeferred());
        self::assertEmpty($manager->getAllDeferred());
        self::assertFalse($manager->isDeferred(['test1']));
        self::assertFalse($manager->isDeferred(['test2']));
    }

}
