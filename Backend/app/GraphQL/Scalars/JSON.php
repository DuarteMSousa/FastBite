<?php

namespace App\GraphQL\Scalars;

use GraphQL\Error\Error;
use GraphQL\Language\AST\BooleanValueNode;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\ListValueNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NullValueNode;
use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;

class JSON extends ScalarType
{
    public string $name = 'JSON';

    public function serialize($value)
    {
        return $value;
    }

    public function parseValue($value)
    {
        return $value;
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null)
    {
        return match (true) {
            $valueNode instanceof StringValueNode => $valueNode->value,
            $valueNode instanceof IntValueNode => (int) $valueNode->value,
            $valueNode instanceof FloatValueNode => (float) $valueNode->value,
            $valueNode instanceof BooleanValueNode => $valueNode->value,
            $valueNode instanceof NullValueNode => null,
            $valueNode instanceof ListValueNode => $this->parseList($valueNode, $variables),
            $valueNode instanceof ObjectValueNode => $this->parseObject($valueNode, $variables),
            default => throw new Error('Unsupported JSON value.'),
        };
    }

    private function parseList(ListValueNode $valueNode, ?array $variables): array
    {
        $value = [];

        foreach ($valueNode->values as $node) {
            $value[] = $this->parseLiteral($node, $variables);
        }

        return $value;
    }

    private function parseObject(ObjectValueNode $valueNode, ?array $variables): array
    {
        $value = [];

        foreach ($valueNode->fields as $field) {
            $value[$field->name->value] = $this->parseLiteral($field->value, $variables);
        }

        return $value;
    }
}
