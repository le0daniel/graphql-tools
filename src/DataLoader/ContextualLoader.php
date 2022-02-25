<?php declare(strict_types=1);

namespace GraphQlTools\DataLoader;

use GraphQL\Deferred;
use GraphQlTools\Context;

final class ContextualLoader
{
    /** @var callable */
    private $loadingFunction;

    private array $queuedData = [];
    private mixed $loadedData = null;

    public function __construct(callable $loadingFunction, private array $arguments, private Context $context)
    {
        $this->loadingFunction = $loadingFunction;
    }

    private function ensureLoadedOnce(){
        if (!is_null($this->loadedData)) {
            return;
        }

        try {
            $this->loadedData = call_user_func($this->loadingFunction, $this->queuedData, $this->arguments, $this->context);

            if (is_null($this->loadedData)) {
                throw new \RuntimeException('Data loader returned null, expected anything but null');
            }
        } catch (\Throwable $exception) {
            $this->loadedData = $exception;
        }
    }

    private function throwOnLoadingException(): void {
        if ($this->loadedData instanceof \Throwable) {
            throw $this->loadedData;
        }
    }

    public function defer(mixed $data, callable $resolveItem): Deferred {
        $this->queuedData[] = $data;
        return new Deferred(function () use ($data, $resolveItem) {
            $this->ensureLoadedOnce();
            $this->throwOnLoadingException();
            return $resolveItem($data, $this->loadedData, $this->context);
        });
    }

}