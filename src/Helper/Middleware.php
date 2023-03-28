<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\GraphQlContext;
use Throwable;

class Middleware
{
    private readonly array $pipes;

    /**
     * @param array<Closure> $pipes
     */
    public function __construct(Closure ...$pipes)
    {
        $this->pipes = $pipes;
    }

    /**
     * @param array<callable(mixed, array, GraphQlContext, ResolveInfo, callable): mixed> $pipes
     * @return static
     */
    public static function create(array $pipes): static
    {
        return new static(...$pipes);
    }

    /**
     * @param Closure $middle
     * @return Closure(mixed, array, GraphQlContext, ResolveInfo): mixed
     */
    public function then(Closure $middle): Closure
    {
        return array_reduce(
            array_reverse($this->pipes), $this->createReducer(), $this->prepareDestination($middle),
        );
    }

    private function prepareDestination(Closure $destination): Closure
    {
        return static function (mixed $value, array $arguments, GraphQlContext $context, ResolveInfo $info) use ($destination) {
            return $destination($value, $arguments, $context, $info);
        };
    }

    private function createReducer(): Closure
    {
        return function ($stack, $pipe) {
            return function (...$args) use ($stack, $pipe) {
                $args[] = $stack;

                try {
                    return $pipe(...$args);
                } catch (Throwable $error) {
                    return $error;
                }
            };
        };
    }

}