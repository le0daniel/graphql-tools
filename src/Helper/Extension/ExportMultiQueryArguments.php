<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Extension;

use Closure;
use GraphQL\Language\AST\ArgumentNode;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Directives\ExportDirective;
use GraphQlTools\Events\VisitFieldEvent;

class ExportMultiQueryArguments extends Extension
{
    public const NAME = 'exportedVariables';

    protected array $exportedVariables = [];

    public function key(): string
    {
        return self::NAME;
    }

    public function isVisibleInResult($context): bool
    {
        return !empty($this->exportedVariables);
    }

    public function getAllExportedVariables(): array {
        $allVariables = [];
        foreach ($this->exportedVariables as $operation => $variables) {
            $allVariables = [...$allVariables, ...$variables];
        }
        return $allVariables;
    }

    private function getDirectiveNode(string $directiveName, ResolveInfo $info): ?DirectiveNode {
        $nodes = $info->fieldNodes;
        if ($nodes->count() !== 1) {
            return null;
        }

        /** @var FieldNode $node */
        $node = $nodes[0];
        $directives = $node->directives;

        /** @var DirectiveNode $directive */
        foreach ($directives as $directive) {
            if ($directive->name->value === $directiveName) {
                return $directive;
            }
        }
        return null;
    }

    private function getDirectiveArguments(DirectiveNode $directive): array {
        $argumentsArray = [];

        /** @var ArgumentNode $argument */
        foreach ($directive->arguments as $argument) {
            $argumentsArray[$argument->name->value] = $argument->value->value;
        }

        return $argumentsArray;
    }

    /**
     * As resolved values can contain more data than they should, we only return primitive values.
     * Example, if you resolve a field which loads data, you might leak full objects, which is not intended.
     * @param mixed $value
     * @return mixed
     */
    protected function getSafeValue(mixed $value): mixed {
        return match (true) {
            is_string($value), is_numeric($value), is_bool($value) => $value,
            default => null,
        };
    }

    public function visitField(VisitFieldEvent $event): ?Closure
    {
        $directive = $this->getDirectiveNode(ExportDirective::NAME, $event->info);
        if (!$directive) {
            return null;
        }

        $operationName = $event->info->operation->name?->value ?? 'anonymous';
        $options = $this->getDirectiveArguments($directive);
        $exportAs = $options['as'];
        $isList = $options['isList'] ?? false;

        return function ($value) use ($exportAs, $operationName, $isList) {
            $isList
                ? $this->exportedVariables[$operationName][$exportAs][] = $this->getSafeValue($value)
                : $this->exportedVariables[$operationName][$exportAs] = $this->getSafeValue($value);
        };
    }

    public function jsonSerialize(): mixed
    {
        return $this->exportedVariables;
    }
}