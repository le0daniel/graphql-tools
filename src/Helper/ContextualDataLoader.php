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
    private $mappingFunction;

    private array $queuedItems = [];
    private mixed $loadedDataOrException = null;

    public function __construct(
        callable      $loadingFunction,
        callable      $mappingFunction,
        private array $arguments,
    ){
        $this->loadingFunction = $loadingFunction;
        $this->mappingFunction = $mappingFunction;
    }

    private function ensureLoadedOnce(Context $context)
    {
        if ($this->loadedDataOrException !== null) {
            return;
        }

        try {
            $this->loadedDataOrException = ($this->loadingFunction)($this->queuedItems, $this->arguments, $context);

            if (is_null($this->loadedDataOrException)) {
                throw new RuntimeException('aggregatedLoadingFunction returned null, expected anything but null.');
            }
        } catch (Throwable $exception) {
            $this->loadedDataOrException = $exception;
        } finally {
            unset($this->queuedItems);
        }
    }

    private function throwOnLoadingException(): void
    {
        if ($this->loadedDataOrException instanceof Throwable) {
            throw $this->loadedDataOrException;
        }
    }

    public function defer(mixed $data, Context $context): Deferred
    {
        $this->queuedItems[] = $data;
        return new Deferred(function () use ($data, $context) {
            $this->ensureLoadedOnce($context);
            $this->throwOnLoadingException();
            return ($this->mappingFunction)($data, $this->arguments, $this->loadedDataOrException, $context);
        });
    }

}