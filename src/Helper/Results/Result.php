<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Results;

use GraphQL\Error\DebugFlag;
use GraphQL\Error\FormattedError;
use GraphQL\Validator\Rules\ValidationRule;
use GraphQlTools\Contract\ExecutionExtension;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Contract\GraphQlResult;
use GraphQlTools\Contract\ProvidesResultExtension;
use GraphQlTools\Helper\Extensions;
use JsonSerializable;
use Throwable;

abstract readonly class Result implements GraphQlResult, JsonSerializable
{
    public function __construct(
        public mixed $data,
        public array $errors,
        public GraphQlContext $context,
        protected array $validationRules = [],
        protected ?Extensions $extensions = null,
    )
    {
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getContext(): GraphQlContext
    {
        return $this->context;
    }

    public function getExtension(string $name): ?ExecutionExtension {
        return $this->extensions?->get($name) ?? null;
    }

    public function getValidationRule(string $name): ?ValidationRule {
        return $this->validationRules[$name] ?? null;
    }

    abstract function appendToResult(array $result): array;

    final public function toArray(int $debug = DebugFlag::NONE): array
    {
        $result = [
            'data' => $this->data,
        ];

        if (!empty($this->errors)) {
            $result['errors'] = $this->formatErrors($debug);
        }

        if ($extensions = $this->serializeAllExtensions($debug)) {
            $result['extensions'] = $extensions;
        }

        return $this->appendToResult($result);
    }

    private function formatErrors(int $debug): array {
        return array_map(
            static fn(Throwable $e) => FormattedError::createFromException($e, $debug),
            $this->errors
        );
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    protected function serializeAllExtensions(int $debug): ?array {
        $serializable = [...$this->validationRules, ...($this->extensions?->getExtensions() ?? [])];
        return empty($serializable) ? null : $this->serializeExtendResults($debug, $serializable);
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