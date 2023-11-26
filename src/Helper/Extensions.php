<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQlTools\Contract\Extension\InteractsWithFieldResolution;
use GraphQlTools\Contract\Extension\ListensToLifecycleEvents;
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
class Extensions
{
    /** @var array<string, ExecutionExtension> */
    private array $extensions = [];

    /**
     * @var array{lifecycleEvents: array<ListensToLifecycleEvents>, fieldResolution: array<InteractsWithFieldResolution>}
     */
    private array $registrations = [
        'lifecycleEvents' => [],
        'fieldResolution' => [],
    ];

    public function __construct(ExecutionExtension ...$extensions)
    {
        foreach ($extensions as $extension) {
            if ($extension instanceof ListensToLifecycleEvents) {
                $this->registrations['lifecycleEvents'][] = $extension;
            }
            if ($extension instanceof InteractsWithFieldResolution) {
                $this->registrations['fieldResolution'][] = $extension;
            }

            $this->extensions[$extension->getName()] = $extension;
        }
    }

    /**
     * @return array<string, ExecutionExtension>
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function get(string $name): ?ExecutionExtension {
        return $this->extensions[$name] ?? null;
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

    public function willResolveField(VisitFieldEvent $event): void
    {
        foreach ($this->registrations['fieldResolution'] as $extension) {
            $extension->visitField($event);
            if ($event->isStopped()) {
                break;
            }
        }
    }

    public function dispatch(Event $event): void
    {
        foreach ($this->registrations['lifecycleEvents'] as $extension) {
            match ($event::class) {
                StartEvent::class => $extension->start($event),
                ParsedEvent::class => $extension->parsed($event),
                EndEvent::class => $extension->end($event),
            };
        }
    }
}
