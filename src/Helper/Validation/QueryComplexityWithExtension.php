<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Validation;

use GraphQL\Error\DebugFlag;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQlTools\Contract\ProvidesResultExtension;

class QueryComplexityWithExtension extends QueryComplexity implements ProvidesResultExtension
{
    public function isVisibleInResult($context): bool
    {
        return true;
    }

    public function key(): string
    {
        return 'complexity';
    }

    public function serialize(int $debug = DebugFlag::NONE): array
    {
        return [
            'max' => $this->maxQueryComplexity,
            'current' => $this->queryComplexity,
        ];
    }
}