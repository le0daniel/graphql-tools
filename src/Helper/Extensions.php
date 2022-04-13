<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQlTools\Contract\Extension;
use GraphQlTools\Events\VisitFieldEvent;
use GraphQlTools\Events\StartEvent;
use GraphQlTools\Events\EndEvent;
use JsonSerializable;

final class Extensions implements JsonSerializable
{

    /** @var Extension[] */
    private array $extensions;

    public function __construct(Extension ...$extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * This is used internally to build and order extensions
     * The extensions array must consist of class names or factories
     * (callable) which create a new instance of an extension. Extensions are
     * considered contextual for each execution and are freshly built on each
     * query.
     *
     * @param array $extensionFactories
     * @return Extensions
     */
    public static function createFromExtensionFactories(array $extensionFactories): Extensions
    {
        $instances = [];
        $columnToSort = [];

        /** @var Extension|callable(): Extension $instance */
        foreach ($extensionFactories as $instance) {
            $instance = is_callable($instance) ? $instance() : new $instance;
            $columnToSort[] = $instance->priority();
            $instances[] = $instance;
        }

        // We sort the instance by priority. This is especially important for tracing to
        // ensure the durations are correct.
        array_multisort($columnToSort, SORT_ASC, $instances);
        return new self(...$instances);
    }

    public function collectValidationRules(): array {
        $validationRules = [];
        foreach ($this->extensions as $extension) {
            if ($rule = $extension->addValidationRule()) {
                $validationRules[] = $rule;
            }
        }
        return $validationRules;
    }

    public function willVisitField(VisitFieldEvent $event): Closure
    {
        $afterStack = [];

        foreach ($this->extensions as $extension) {
            if ($afterEvent = $extension->visitField($event)) {
                array_unshift($afterStack, $afterEvent);
            }
        }

        return static function (mixed $resolvedValue) use ($afterStack) {
            foreach ($afterStack as $next) {
                $next($resolvedValue);
            }
            return $resolvedValue;
        };
    }

    public function dispatchStartEvent(StartEvent $event)
    {
        foreach ($this->extensions as $extension) {
            $extension->start($event);
        }
    }

    public function dispatchEndEvent(EndEvent $event)
    {
        foreach ($this->extensions as $extension) {
            $extension->end($event);
        }
    }

    public function jsonSerialize(): array
    {
        $extensionData = [];

        foreach ($this->extensions as $extension) {
            if ($data = $extension->jsonSerialize()) {
                $extensionData[$extension->key()] = $data;
            }
        }

        return $extensionData;
    }
}
