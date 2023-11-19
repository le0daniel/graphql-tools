<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Results;

use GraphQL\Error\DebugFlag;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Validator\Rules\ValidationRule;
use GraphQlTools\Contract\ExecutionExtension;
use GraphQlTools\Contract\ProvidesResultExtension;
use Throwable;

final class GraphQlResult extends ExecutionResult
{
    /**
     * @var array<string, ValidationRule>
     */
    private readonly array $validationRules;

    /**
     * @var array<string, ExecutionExtension>
     */
    private readonly array $executionExtensions;
    private readonly mixed $context;

    private function __construct(array $data = null, array $errors = [], array $extensions = [])
    {
        parent::__construct($data, $errors, $extensions);
    }

    public static function fromExecutionResult(
        ExecutionResult $result,
        mixed           $context,
        array           $validationRules,
        array           $executionExtensions,
    ): self
    {
        $instance = new self($result->data, $result->errors, $result->extensions);
        $instance->validationRules = $validationRules;
        $instance->executionExtensions = $executionExtensions;
        $instance->context = $context;
        return $instance;
    }

    public function toArray(int $debug = DebugFlag::NONE): array
    {
        $result = parent::toArray($debug);

        $extensions = $this->serializeExtendResults($debug, [...$this->validationRules, ...$this->executionExtensions]);

        if (!empty($extensions)) {
            $result['extensions'] = $extensions;
        }
        return $result;
    }

    /**
     * @template T of ExecutionExtension
     * @param class-string<ExecutionExtension> $name
     * @return T|null
     */
    public function getExtension(string $name): ?ExecutionExtension
    {
        return $this->executionExtensions[$name] ?? null;
    }

    /**
     * @template T of ValidationRule
     * @param class-string<ValidationRule> $name
     * @return T|null
     */
    public function getValidationRule(string $name): ?ValidationRule
    {
        return $this->validationRules[$name] ?? null;
    }

    private function serializeExtendResults(int $debug, array $items): array
    {
        $serialized = [];
        foreach ($items as $item) {
            if (!$item instanceof ProvidesResultExtension || !$item->isVisibleInResult($this->context)) {
                continue;
            }

            try {
                if ($data = $item->serialize($debug)) {
                    $serialized[$item->key()] = $data;
                }
            } catch (Throwable $throwable) {
                $serialized[$item->key()] = $debug >= DebugFlag::INCLUDE_DEBUG_MESSAGE
                    ? $throwable->getMessage()
                    : "Failed to serialize.";
            }
        }
        return $serialized;
    }
}