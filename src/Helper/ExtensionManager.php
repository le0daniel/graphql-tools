<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Events\EndEvent;
use GraphQlTools\Events\StartEvent;
use GraphQlTools\Events\VisitFieldEvent;
use GraphQlTools\Helper\Extension\Extension;
use Throwable;

final class ExtensionManager
{
    /** @var Extension[] */
    private readonly array $extensions;

    public function __construct(Extension ...$extensions)
    {
        $this->extensions = $extensions;
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

        /** @var Extension|Closure(): Extension $instance */
        foreach ($extensionFactories as $classNameOrCallable) {
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

    public function collect(GraphQlContext $context): array
    {
        $extensionData = [];

        foreach ($this->extensions as $extension) {
            if ($extension->isVisibleInResult($context)) {
                try {
                    $extensionData[$extension->key()] = $extension->jsonSerialize();
                } catch (Throwable) {
                    $extensionData[$extension->key()] = "Error collecting data form extension.";
                }
            }
        }

        return $extensionData;
    }
}
