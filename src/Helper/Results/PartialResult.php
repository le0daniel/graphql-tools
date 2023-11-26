<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Results;

use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Helper\Extensions;

readonly class PartialResult extends Result
{
    public function __construct(
        mixed          $data,
        array          $errors,
        GraphQlContext $context,
        array          $validationRules = [],
        ?Extensions    $extensions = null,
        public bool    $hasNext = false,
        public ?string $label = null,
        public ?array  $path = null,
    )
    {
        parent::__construct($data, $errors, $context, $validationRules, $extensions);
    }

    function appendToResult(array $result): array
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