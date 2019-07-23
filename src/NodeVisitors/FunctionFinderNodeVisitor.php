<?php

namespace Ganlv\Z5EncryptDecompiler\NodeVisitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class FunctionFinderNodeVisitor extends NodeVisitorAbstract
{
    /** @var string */
    private $functionNameToFind;
    public $functionNode = null;

    public function __construct(string $functionNameToFind)
    {
        $this->functionNameToFind = $functionNameToFind;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Function_
            || $node instanceof Node\Stmt\ClassMethod) {
            if ($node->name->name === $this->functionNameToFind) {
                $this->functionNode = $node;
            }
        }
    }
}