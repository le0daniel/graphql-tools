<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\Visitor;
use GraphQL\Type\Schema;
use GraphQL\Utils\TypeInfo;
use GraphQlTools\Contract\Extension\InteractsWithFieldResolution;
use GraphQlTools\Contract\Extension\ListensToLifecycleEvents;
use GraphQlTools\Contract\Extension\ManipulatesAst;
use GraphQlTools\Data\ValueObjects\Events\Event;
use GraphQlTools\Contract\ExecutionExtension;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Data\ValueObjects\Events\EndEvent;
use GraphQlTools\Data\ValueObjects\Events\ParsedEvent;
use GraphQlTools\Data\ValueObjects\Events\StartEvent;
use GraphQlTools\Data\ValueObjects\Events\ValidatedEvent;
use GraphQlTools\Data\ValueObjects\Events\VisitFieldEvent;

/**
 * @internal
 */
class Extensions
{
    /** @var array<string, ExecutionExtension> */
    private array $extensions = [];

    /**
     * @var array{lifecycleEvents: array<ListensToLifecycleEvents>, fieldResolution: array<InteractsWithFieldResolution>, manipulateAst: array<ManipulatesAst>}
     */
    private array $registrations = [
        'lifecycleEvents' => [],
        'fieldResolution' => [],
        'manipulateAst' => [],
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
            if ($extension instanceof ManipulatesAst) {
                $this->registrations['manipulateAst'][] = $extension;
            }

            $this->extensions[$extension->getName()] = $extension;
        }
    }

    /**
     * @return array<string, ExecutionExtension>
     */
    public function getArray(): array
    {
        return $this->extensions;
    }

    public function get(string $name): ?ExecutionExtension
    {
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

    public function manipulateAst(DocumentNode $node, Schema $schema, ?array $variables): DocumentNode
    {
        if (empty($this->registrations['manipulateAst'])) {
            return $node;
        }

        $typeInfo = new TypeInfo($schema);
        $visitors = [];
        foreach ($this->registrations['manipulateAst'] as $extension) {
            if ($visitor = $extension->visitor($schema, $variables, $typeInfo)) {
                $visitors[] = $visitor;
            }
        }

        return Visitor::visit(
            $node,
            Visitor::visitWithTypeInfo(
                $typeInfo,
                Visitor::visitInParallel($visitors)
            )
        );
    }

    public function dispatch(Event $event): void
    {
        foreach ($this->registrations['lifecycleEvents'] as $extension) {
            match ($event::class) {
                StartEvent::class => $extension->start($event),
                ParsedEvent::class => $extension->parsed($event),
                ValidatedEvent::class => $extension->validated($event),
                EndEvent::class => $extension->end($event),
            };
        }
    }
}
