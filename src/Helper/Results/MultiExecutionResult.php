<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Results;


use GraphQL\Executor\ExecutionResult;

final class MultiExecutionResult extends ExecutionResult
{
    public function addResult(ExecutionResult $result): void {
        if (!empty($result->data)) {
            $this->data = array_merge($this->data ?? [], $result->data);
        }

        if (!empty($result->errors)) {
            array_push($this->errors, ...$result->errors);
        }
    }

}