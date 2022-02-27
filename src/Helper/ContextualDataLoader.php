<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use GraphQL\Deferred;
use GraphQlTools\Context;
use RuntimeException;
use Throwable;

final class ContextualDataLoader
{
    /** @var callable */
    private $loadingFunction;

    private array $queuedData = [];
    private mixed $loadedDataOrException = null;

    /**
     * The Aggregated Loading Function should be of Format: fn(array $queuedData, array $validatedArguments, Context $context): mixed
     *
     * @param callable $aggregatedLoadingFunction
     * @param array $arguments
     * @param Context $context
     */
    public function __construct(
        callable $aggregatedLoadingFunction,
        private array $arguments,
        private Context $context
    ){
        $this->loadingFunction = $aggregatedLoadingFunction;
    }

    private function ensureLoadedOnce()
    {
        if (!is_null($this->loadedDataOrException)) {
            return;
        }

        try {
            $this->loadedDataOrException = ($this->loadingFunction)($this->queuedData, $this->arguments, $this->context);

            unset($this->arguments);
            unset($this->queuedData);

            if (is_null($this->loadedDataOrException)) {
                throw new RuntimeException('aggregatedLoadingFunction returned null, expected anything but null.');
            }
        } catch (Throwable $exception) {
            $this->loadedDataOrException = $exception;
        }
    }

    private function throwOnLoadingException(): void
    {
        if ($this->loadedDataOrException instanceof Throwable) {
            throw $this->loadedDataOrException;
        }
    }

    public function defer(mixed $data, callable $resolveItem): Deferred
    {
        $this->queuedData[] = $data;
        return new Deferred(function () use ($data, $resolveItem) {
            $this->ensureLoadedOnce();
            $this->throwOnLoadingException();
            return $resolveItem($data, $this->loadedDataOrException, $this->context);
        });
    }

}