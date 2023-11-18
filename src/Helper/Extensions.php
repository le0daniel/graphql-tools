<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQlTools\Data\ValueObjects\Events\Event;
use GraphQlTools\Contract\ExecutionExtension;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Data\ValueObjects\Events\EndEvent;
use GraphQlTools\Data\ValueObjects\Events\ParsedEvent;
use GraphQlTools\Data\ValueObjects\Events\StartEvent;
use GraphQlTools\Data\ValueObjects\Events\VisitFieldEvent;

/**
 * @internal
 */
final readonly class Extensions
{
    /** @var ExecutionExtension[] */
    private array $extensions;

    public function __construct(ExecutionExtension ...$extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * @return array<string, ExecutionExtension>
     */
    public function getKeyedExtensions(): array {
        $keyedExtensions = [];
        foreach ($this->extensions as $extension) {
            $keyedExtensions[$extension->getName()] = $extension;
        }
        return $keyedExtensions;
    }

    /**
     * This is used internally to build and order extensions
     * The extensions array must consist of class names or factories
     * (callable) which create a new instance of an extension. Extensions are
     * considered contextual for each execution and are freshly built on each
     * query.
     *
     * @template T
     * @param GraphQlContext $context
     * @param array<Closure|class-string<T>> $extensionFactories
     * @return Extensions
     */
    public static function createFromExtensionFactories(GraphQlContext $context, array $extensionFactories): Extensions
    {
        $instances = [];
        $columnToSort = [];

        foreach ($extensionFactories as $classNameOrCallable) {
            /** @var ExecutionExtension|Closure(): ExecutionExtension $instance */
            $instance = $classNameOrCallable instanceof Closure ? $classNameOrCallable($context) : new $classNameOrCallable;
            if (!$instance || !$instance->isEnabled()) {
                continue;
            }

            $columnToSort[] = $instance->priority();
            $instances[] = $instance;
        }

        // We sort the instance by priority. This is especially important for tracing to
        // ensure the durations are correct.
        array_multisort($columnToSort, SORT_ASC, $instances);
        return new self(...$instances);
    }

    public function willResolveField(VisitFieldEvent $event): Closure
    {
        $afterCallStack = [];

        foreach ($this->extensions as $extension) {
            if ($afterEvent = $extension->visitField($event)) {
                array_unshift($afterCallStack, $afterEvent);
            }
        }

        return static function (mixed $resolvedValue) use (&$afterCallStack) {
            foreach ($afterCallStack as $next) {
                $next($resolvedValue);
            }

            // Extensions should not modify the resolved value, they can only read it.
            // Use middlewares to actually change the behaviour
            return $resolvedValue;
        };
    }

    public function dispatch(Event $event): void {
        foreach ($this->extensions as $extension) {
            match ($event::class) {
                StartEvent::class => $extension->start($event),
                ParsedEvent::class => $extension->parsed($event),
                EndEvent::class => $extension->end($event),
            };
        }
    }
}
