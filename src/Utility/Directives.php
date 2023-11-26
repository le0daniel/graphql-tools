<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use GraphQL\Executor\Values;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\ResolveInfo;

final class Directives
{
    private const INTERNAL_DIRECTIVES = [
        Directive::INCLUDE_NAME => true,
        Directive::SKIP_NAME => true,
    ];

    public static function getNamesByResolveInfo(ResolveInfo $info): array
    {
        /** @var FieldNode $fieldNode */
        $fieldNode = $info->fieldNodes[0] ?? null;
        if (!$fieldNode instanceof FieldNode || empty($fieldNode->directives)) {
            return [];
        }

        $directives = [];
        /** @var DirectiveNode $directiveNode */
        foreach ($fieldNode->directives as $directiveNode) {
            if (self::INTERNAL_DIRECTIVES[$directiveNode->name->value] ?? false) {
                continue;
            }
            $directives[] = $directiveNode->name->value;
        }
        return $directives;
    }

    public static function createPipes(ResolveInfo $info, array $names): array
    {
        $pipes = [];
        foreach ($names as $name) {
            $directive = $info->schema->getDirective($name);
            if (isset($directive->config['middleware'])) {
                $executor = $directive->config['middleware'](Values::getDirectiveValues(
                    $directive,
                    $info->fieldNodes[0],
                    $info->variableValues
                ));
                if ($executor) {
                    $pipes[] = $executor;
                }
            }
        }
        return $pipes;
    }
}