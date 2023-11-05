<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQlTools\Contract\ExecutionExtension;
use GraphQlTools\Events\EndEvent;
use GraphQlTools\Events\StartEvent;
use GraphQlTools\Events\VisitFieldEvent;

/**
 * @internal
 */
final readonly class ExtensionManager
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

    public function getExtensionsCount(): int {
        return count($this->extensions);
    }

    /**
     * This is used internally to build and order extensions
     * The extensions array must consist of class names or factories
     * (callable) which create a new instance of an extension. Extensions are
     * considered contextual for each execution and are freshly built on each
     * query.
     *
     * @template T
     * @param array<Closure|class-string<T>> $extensionFactories
     * @return ExtensionManager
     */
    public static function createFromExtensionFactories(array $extensionFactories): ExtensionManager
    {
        $instances = [];
        $columnToSort = [];

        foreach ($extensionFactories as $classNameOrCallable) {
            /** @var ExecutionExtension|Closure(): ExecutionExtension $instance */
            $instance = $classNameOrCallable instanceof Closure ? $classNameOrCallable() : new $classNameOrCallable;
            if (!$instance->isEnabled()) {
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

    public function dispatchStartEvent(StartEvent $event): void
    {
        foreach ($this->extensions as $extension) {
            $extension->start($event);
        }
    }

    public function dispatchEndEvent(EndEvent $event): void
    {
        foreach ($this->extensions as $extension) {
            $extension->end($event);
        }
    }
}
