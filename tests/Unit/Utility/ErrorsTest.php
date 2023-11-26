<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Utility;

use GraphQL\Error\Error;
use GraphQlTools\Utility\Errors;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ErrorsTest extends TestCase
{
    use ProphecyTrait;

    private function error(array $path): Error {
        $error = $this->prophesize(Error::class)->reveal();
        $error->path = $path;
        return $error;
    }

    public function testFilterByPath()
    {
        $errors = [
            $this->error(['query', 'sub', 1, 'id', '4']),
            $this->error(['query', 'sub', 3, 'id', '4'])
        ];

        self::assertCount(1, Errors::filterByPath($errors, ['query', 'sub', 1]));
        self::assertCount(1, Errors::filterByPath($errors, ['query', 'sub', 3]));
        self::assertCount(0, Errors::filterByPath($errors, ['query', 'sub', 2]));
        self::assertCount(1, Errors::filterByPath($errors, ['query', 'sub', 1, 'id', 4]));
        self::assertCount(2, Errors::filterByPath($errors, ['query', 'sub']));
    }
}
