<?php

declare(strict_types=1);

namespace GraphQlTools\Execution;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\Extension;
use GraphQlTools\Utility\Middlewares;
use GraphQlTools\Utility\Time;

final class Extensions implements \JsonSerializable {

    public const START_EVENT = 'start';
    public const END_EVENT = 'end';
    public const FIELD_RESOLUTION_EVENT = 'fieldResolution';

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
     * @return \GraphQlTools\Execution\Extensions
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
    public function middlewareFieldResolution(mixed $typeData, array $arguments, ResolveInfo $info): Closure {
        $eventTime = Time::nanoSeconds();

        return Middlewares::executeAndReturnNext(
            $this->extensions,
            /** @suppress PhanTypeMismatchArgument */
            fn(Extension $extension) => $extension->fieldResolution($eventTime, $typeData, $arguments, $info)
        );
    }

    /**
     * @suppress PhanTypeMismatchArgument
     *
     * @param string $eventName
     * @param ...$payload
     * @return void
     */
    public function dispatch(string $eventName, ... $payload): void {
        // The time of the event is always added as the first
        $eventTime = Time::nanoSeconds();

        switch ($eventName) {
            case self::START_EVENT:
                array_walk($this->extensions, static fn(Extension $extension) => $extension->start($eventTime, ... $payload));
                return;
            case self::END_EVENT:
                array_walk($this->extensions, static fn(Extension $extension) => $extension->end($eventTime));
                return;
        }

        throw new \RuntimeException("Unexpected event with name: `{$eventName}`");
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
