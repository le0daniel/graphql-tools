<?php

declare(strict_types=1);

namespace GraphQlTools\Execution;

use Closure;
use GraphQlTools\Contract\Extension;
use GraphQlTools\Utility\Stack;
use GraphQlTools\Utility\Time;

final class ExtensionManager implements \JsonSerializable {

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
     * can be instantiated or a factory (callable)
     *
     * @param array $extensions
     * @return \GraphQlTools\Execution\ExtensionManager
     */
    public static function create(array $extensions): ExtensionManager {
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
     * @suppress PhanTypeMismatchArgument
     *
     * @param string $eventName
     * @param ...$payload
     * @return \Closure|null
     */
    public function dispatch(string $eventName, ... $payload): Closure|null {
        // The time of the event is always added as the first
        $eventTime = Time::nanoSeconds();

        switch ($eventName) {
            case self::START_EVENT:
                Stack::execute(
                    $this->extensions,
                    static fn(Extension $extension) => $extension->start($eventTime, ... $payload)
                );
                return null;
            case self::END_EVENT:
                Stack::execute(
                    $this->extensions,
                    static fn(Extension $extension) => $extension->end($eventTime)
                );
                return null;
            case self::FIELD_RESOLUTION_EVENT:
                return Stack::executeAndReturnCallback(
                    $this->extensions,
                    /** @suppress PhanTypeMismatchArgument */
                    fn(Extension $extension) => $extension->fieldResolution($eventTime, ... $payload)
                );
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
