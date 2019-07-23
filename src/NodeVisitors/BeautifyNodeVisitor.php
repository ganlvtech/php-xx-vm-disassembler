<?php

namespace Ganlv\Z5EncryptDecompiler\NodeVisitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class BeautifyNodeVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Expr\Array_) {
            $node->setAttribute('kind', Node\Expr\Array_::KIND_SHORT);
        } elseif ($node instanceof Node\Expr\Variable
            && $node->name instanceof Node\Scalar\String_) {
            $node->name = $node->name->value;
        }
    }
}