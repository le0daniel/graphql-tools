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
    /** @var callable */
    private $resolveItemFunction;

    private array $queuedItems = [];
    private mixed $loadedDataOrException = null;

    public function __construct(
        callable $aggregatedLoadingFunction,
        callable $resolveItemFunction,
        private array $arguments,
        private readonly Context $context
    ){
        $this->loadingFunction = $aggregatedLoadingFunction;
        $this->resolveItemFunction = $resolveItemFunction;
    }

    private function ensureLoadedOnce()
    {
        if ($this->loadedDataOrException !== null) {
            return;
        }

        try {
            $this->loadedDataOrException = ($this->loadingFunction)($this->queuedItems, $this->arguments, $this->context);

            if (is_null($this->loadedDataOrException)) {
                throw new RuntimeException('aggregatedLoadingFunction returned null, expected anything but null.');
            }
        } catch (Throwable $exception) {
            $this->loadedDataOrException = $exception;
        } finally {
            unset($this->arguments);
            unset($this->queuedItems);
        }
    }

    private function throwOnLoadingException(): void
    {
        if ($this->loadedDataOrException instanceof Throwable) {
            throw $this->loadedDataOrException;
        }
    }

    public function defer(mixed $data): Deferred
    {
        $this->queuedItems[] = $data;
        return new Deferred(function () use ($data) {
            $this->ensureLoadedOnce();
            $this->throwOnLoadingException();
            return ($this->resolveItemFunction)($data, $this->loadedDataOrException, $this->context);
        });
    }

}