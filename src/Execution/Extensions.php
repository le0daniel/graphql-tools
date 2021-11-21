<?php

declare(strict_types=1);

namespace GraphQlTools\Execution;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\Extension;
use GraphQlTools\Events\FieldResolutionEvent;
use GraphQlTools\Events\StartEvent;
use GraphQlTools\Events\StopEvent;
use GraphQlTools\Utility\Middlewares;
use GraphQlTools\Utility\Time;
use RuntimeException;

final class Extensions implements \JsonSerializable {

    /** @var Extension[] */
    private array $extensions;

    public function __construct(Extension ... $extensions){
        $this->extensions = $extensions;
    }

    /**
     * This is used internally to build and order the extensions
     * The extensions array must consist of stateless classes which
     * can be instantiated or a factory (callable). Extensions are
     * considered contextual for each execution.
     *
     * @param array $extensions
     * @return Extensions
     */
    public static function create(array $extensions): Extensions {
        $instances = [];
        $columnToSort = [];

        /** @var Extension|callable(): Extension $instance */
        foreach ($extensions as $instance) {
            $instance = is_callable($instance) ? $instance() : new $instance;
            $columnToSort[] = $instance->priority();
            $instances[] = $instance;
        }

        // We sort the instance by priority. This is especially important for tracing to
        // ensure the durations are correct.
        array_multisort($columnToSort, SORT_ASC, $instances);
        return new self(...$instances);
    }

    /**
     * @param mixed $typeData
     * @param array $arguments
     * @param ResolveInfo $info
     * @return Closure
     */
    public function middlewareFieldResolution(FieldResolutionEvent $event): Closure {

        return Middlewares::executeAndReturnNext(
            $this->extensions,
            /** @suppress PhanTypeMismatchArgument */
            static fn(Extension $extension) => $extension->fieldResolution($event)
        );
    }

    public function dispatch(StopEvent|StartEvent $event): void {
        switch ($event::class) {
            case StartEvent::class:
                array_walk($this->extensions, static fn(Extension $extension) => $extension->start($event));
                return;
            case StopEvent::class:
                array_walk($this->extensions, static fn(Extension $extension) => $extension->end($event));
                return;
        }

        $eventName = $event::class;
        throw new RuntimeException("Unexpected event with name: `{$eventName}`");
    }

    public function jsonSerialize(): array {
        $extensionData = [];

        foreach ($this->extensions as $extension) {
            if ($data = $extension->jsonSerialize()) {
                $extensionData[$extension->key()] = $data;
            }
        }

        return $extensionData;
    }
}
