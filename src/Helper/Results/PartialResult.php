<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Results;

use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Helper\Extensions;
use GraphQlTools\Helper\OperationContext;
use GraphQlTools\Helper\ValidationRules;

readonly class PartialResult extends Result
{
    public function __construct(
        mixed            $data,
        array            $errors,
        GraphQlContext   $context,
        ValidationRules $validationRules = null,
        Extensions      $extensions = null,
        public bool      $hasNext = false,
        public ?string   $label = null,
        public ?array    $path = null,
    )
    {
        parent::__construct($data, $errors, $context, $validationRules, $extensions);
    }

    public function shouldSerializeExtensions(): bool
    {
        return !$this->hasNext;
    }

    public static function first(
        mixed            $data,
        array            $errors,
        OperationContext $context,
    ): self
    {
        return new self(
            $data,
            $errors,
            $context->context,
            $context->validationRules,
            $context->extensions,
            true,
            null,
            null,
        );
    }

    public static function part(
        mixed            $data,
        array            $errors,
        bool             $hasNext,
        OperationContext $context,
        ?string          $label,
        ?array           $path,
    ): self
    {
        return new self(
            $data,
            $errors,
            $context->context,
            $context->validationRules,
            $context->extensions,
            $hasNext,
            $label,
            $path
        );
    }

    protected function appendToResult(array $result): array
    {
        $result['hasNext'] = $this->hasNext;

        if ($this->label) {
            $result['label'] = $this->label;
        }

        if ($this->path) {
            $result['path'] = $this->path;
        }

        return $result;
    }
}