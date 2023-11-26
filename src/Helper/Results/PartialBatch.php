<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Results;

use GraphQL\Error\DebugFlag;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Contract\GraphQlResult;
use JsonSerializable;

final readonly class PartialBatch implements JsonSerializable, GraphQlResult
{

    /**
     * @param array<PartialResult> $batch
     */
    public function __construct(
        private array          $batch,
        private GraphQlContext $context,
        public bool            $hasNext,
    )
    {
    }

    /**
     * @return PartialResult[]
     */
    public function getResults(): array
    {
        return $this->batch;
    }

    public function toArray(int $debug = DebugFlag::NONE): array
    {
        return array_map(fn(PartialResult $result) => $result->toArray($debug), $this->batch);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getContext(): GraphQlContext
    {
        return $this->context;
    }

    public function getErrors(): array
    {
        $errors = [];
        foreach ($this->batch as $result) {
            array_push($errors, ...$result->getErrors());
        }
        return $errors;
    }
}