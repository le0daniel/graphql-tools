<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\GraphQlContext;
use Throwable;

final readonly class Middleware
{
    private array $pipes;

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
            array_reverse($this->pipes), $this->createReducer(), $middle,
        );
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