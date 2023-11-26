<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\GraphQlContext;
use Throwable;

/**
 * @internal
 */
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
    public static function create(array $pipes): Middleware
    {
        return new Middleware(...$pipes);
    }

    /**
     * @param Closure $middle
     * @return Closure(mixed, array, GraphQlContext, ResolveInfo): mixed
     */
    public function then(Closure $middle): Closure
    {
        if (empty($this->pipes)) {
            return $middle;
        }

        return array_reduce(
            array_reverse($this->pipes), $this->reducer(...), $middle,
        );
    }

    private function reducer($stack, $pipe): Closure
    {
        return function (...$args) use ($stack, $pipe) {
            $args[] = $stack;

            try {
                return $pipe(...$args);
            } catch (Throwable $error) {
                return $error;
            }
        };
    }

}