<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Results;

use GraphQL\Error\ClientAware;
use GraphQL\Error\DebugFlag;
use GraphQL\Error\Error as GraphQlError;
use GraphQL\Error\FormattedError;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Validator\Rules\ValidationRule;
use GraphQlTools\Contract\ProvidesErrorExtensions;
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
        $instance->setErrorFormatter(self::formatErrorsWithAdditionalExtensions(...));
        return $instance;
    }

    public static function formatErrorsWithAdditionalExtensions(GraphQlError $error): array
    {
        $formatted = FormattedError::createFromException($error);
        $previous = $error->getPrevious();

        if (!$previous instanceof ClientAware || !$previous instanceof ProvidesErrorExtensions) {
            return $formatted;
        }

        /** @var Throwable&ClientAware&ProvidesErrorExtensions $previous */
        if (!$previous->isClientSafe()) {
            return $formatted;
        }

        $extensions = $previous->getExtensions() + ($formatted['extensions'] ?? []);
        if (!empty($extensions)) {
            $formatted['extensions'] = $extensions;
        }

        return $formatted;
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
            if (!$item instanceof ProvidesResultExtension) {
                continue;
            }

            if (!$item->isVisibleInResult($this->context)) {
                continue;
            }

            try {
                $serialized[$item->key()] = $item->serialize($debug);
            } catch (Throwable $throwable) {
                $serialized[$item->key()] = $debug >= DebugFlag::INCLUDE_DEBUG_MESSAGE
                    ? $throwable->getMessage()
                    : "Failed to serialize.";
            }
        }
        return $serialized;
    }
}