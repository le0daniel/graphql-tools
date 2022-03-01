<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\Extension;
use GraphQlTools\Events\FieldResolutionEvent;
use GraphQlTools\Events\StartEvent;
use GraphQlTools\Events\EndEvent;

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
     * @param array $extensionFactories
     * @return Extensions
     */
    public static function createFromExtensionFactories(array $extensionFactories): Extensions {
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

    /**
     * @param mixed $typeData
     * @param array $arguments
     * @param ResolveInfo $info
     * @return Closure
     */
    public function visitField(FieldResolutionEvent $event): Closure {
        $afterStack = [];

        foreach ($this->extensions as $extension) {
            if ($afterEvent = $extension->visitField($event)) {
                array_unshift($afterStack, $afterEvent);
            }
        }

        return function (mixed $resolvedValue) use ($afterStack) {
            foreach ($afterStack as $next) {
                $resolvedValue = $next($resolvedValue);
            }
            return $resolvedValue;
        };
    }

    public function dispatchStartEvent(StartEvent $event){
        foreach ($this->extensions as $extension) {
            $extension->start($event);
        }
    }

    public function dispatchEndEvent(EndEvent $event){
        foreach ($this->extensions as $extension) {
            $extension->end($event);
        }
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
