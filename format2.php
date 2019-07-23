<?php

use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter\Standard;

ini_set('xdebug.max_nesting_level', 10000);

require 'vendor/autoload.php';

$code = file_get_contents('tests/samples/t2.php_');

$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
try {
    $ast = $parser->parse($code);
} catch (Error $error) {
    echo "Parse error: {$error->getMessage()}\n";
    return;
}

class FunctionLikeNodeVisitor extends NodeVisitorAbstract
{
    public $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\FunctionLike) {
            /** @var $node \PhpParser\Node\Stmt\Function_ */
            return ($this->callback)($node);
        }
        return null;
    }
}

class FunctionLocalVariableRenameNodeVisitor extends NodeVisitorAbstract
{
    public $varMap = [];
    public $argCount = 0;
    public $localVarCount = 0;

    public static function isUnreadable($string)
    {
        return 1 === preg_match('/\W/', $string);
    }

    public function generateArgName()
    {
        return 'arg' . ($this->argCount++);
    }

    public function generateLocalVarName()
    {
        return 'v' . ($this->localVarCount++);
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\FunctionLike) {
            /** @var $node \PhpParser\Node\Stmt\Function_ */
            foreach ($node->params as $param) {
                $name = $param->var->name;
                if (array_key_exists($name, $this->varMap)) {
                    $param->var->name = $this->varMap[$name];
                } elseif (self::isUnreadable($name)) {
                    $this->varMap[$name] = $this->generateArgName();
                    $param->var->name = $this->varMap[$name];
                }
            }
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Expr\Variable) {
            $name = $node->name;
            if (is_string($name)) {
                if (array_key_exists($name, $this->varMap)) {
                    $node->name = $this->varMap[$name];
                } elseif (self::isUnreadable($name)) {
                    $this->varMap[$name] = $this->generateLocalVarName();
                    $node->name = $this->varMap[$name];
                }
            }
        }
        return null;
    }
}

$nodeVisitor = new FunctionLikeNodeVisitor(function ($node) {
    /** @var $node \PhpParser\Node\Stmt\Function_ */
    $nodeVisitor = new FunctionLocalVariableRenameNodeVisitor();
    $traverser = new NodeTraverser();
    $traverser->addVisitor($nodeVisitor);
    $ast = $traverser->traverse([$node]);
    return $ast[0];
});
$traverser = new NodeTraverser();
$traverser->addVisitor($nodeVisitor);
$ast = $traverser->traverse($ast);

$prettyPrinter = new Standard;
$newCode = $prettyPrinter->prettyPrintFile($ast);

file_put_contents('tests/samples/t2_formatted.php_', $newCode);
