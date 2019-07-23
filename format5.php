<?php

use PhpParser\Error;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter\Standard;

require 'vendor/autoload.php';

ini_set('xdebug.max_nesting_level', 10000);

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
        } elseif ($node instanceof Node\Scalar\String_) {
            $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
            try {
                $ast = $parser->parse('<?php ' . $node->value);
                if (!empty($ast)) {
                    $nodeVisitor = new FunctionStringReplaceNodeVisitor;
                    $traverser = new NodeTraverser();
                    $traverser->addVisitor($this);
                    $traverser->addVisitor($nodeVisitor);
                    $ast = $traverser->traverse($ast);

                    $nodeVisitor2 = new BeautifyNodeVisitor();
                    $traverser = new NodeTraverser();
                    $traverser->addVisitor($nodeVisitor2);
                    $ast = $traverser->traverse($ast);

                    $prettyPrinter = new Standard;
                    $newCode = $prettyPrinter->prettyPrint($ast);
                    $node->value = $newCode;
                }
            } catch (Error $error) {
            }
        }
        return null;
    }
}

class FunctionStringReplaceNodeVisitor extends NodeVisitorAbstract
{
    public $varMap = [
        0 => 'openssl_decrypt',
        1 => 'resource',
        2 => 'string',
        3 => '__construct',
        4 => 'undef',
        5 => 'microtime',
        6 => 'int',
        7 => 'hi debugger~',
        8 => 'object',
        9 => 'false',
        10 => '1;',
        11 => 'Z5Encrypt VM Error: Unhandled',
        12 => 'double',
        13 => 'true',
        14 => 'reference',
        15 => 'func1',
        16 => 'constant',
        17 => 'callable',
        18 => 'null',
        19 => 'intval',
        20 => 'count',
        21 => 'strpos',
        22 => 'call_user_func_array',
        23 => 'gzinflate',
        24 => '#opcodeString',
        25 => 'new',
        26 => 'AES-128-ECB',
        27 => 'bool',
        28 => 'array_key_exists',
        29 => 'void',
        30 => 'is_',
        31 => 'error',
        32 => 'na/Nz',
        33 => 'ptr',
        34 => 'array_pop',
        35 => 'array',
        36 => 'array_search',
        37 => 'c/N*',
        38 => 'substr',
        39 => 'strlen',
        40 => 'usleep',
        41 => 'indirect',
        42 => 'base64_decode',
        43 => 'iterable',
        44 => 'unpack',
    ];
    public $varName = 'v1';

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Expr\ArrayDimFetch
            && $node->var instanceof Node\Expr\Variable
            && $node->var->name === $this->varName
            && $node->dim instanceof Node\Scalar\LNumber) {
            return new Node\Scalar\String_($this->varMap[$node->dim->value]);
        }
        return null;
    }
}

class BeautifyNodeVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if (($node instanceof Node\Expr\FuncCall
                || $node instanceof Node\Expr\StaticCall
                || $node instanceof Node\Expr\MethodCall)
            && $node->name instanceof Node\Scalar\String_) {
            $node->name = new Node\Name($node->name->value);
        }
    }
}

class ReplaceGetValueNodeVisitor extends NodeVisitorAbstract
{
    public static function formatCode($node)
    {
        static $prettyPrinter = null;
        if (is_null($prettyPrinter)) {
            $prettyPrinter = new Standard;
        }
        return $prettyPrinter->prettyPrintExpr($node);
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof \PhpParser\Node\Expr\Ternary
            && $node->cond instanceof \PhpParser\Node\Expr\BinaryOp\Identical
            && $node->cond->left // TODO
            && $node->cond->right instanceof \PhpParser\Node\Scalar\String_
            && $node->cond->right->value === ''
            && $node->if instanceof \PhpParser\Node\Scalar\String_
            && $node->if->value === ''
            && $node->else instanceof \PhpParser\Node\Expr\Ternary
            && $node->else->cond instanceof \PhpParser\Node\Expr\FuncCall
            && $node->else->cond->name instanceof \PhpParser\Node\Name
            && count($node->else->cond->name->parts) === 1
            && $node->else->cond->name->parts[0] === 'array_key_exists'
            && count($node->else->cond->args) === 2
            && $node->else->cond->args[0] instanceof \PhpParser\Node\Arg
            && $node->else->cond->args[0]->value // TODO == $node->cond->left
            && $node->else->cond->args[0]->byRef === false
            && $node->else->cond->args[0]->unpack === false
            && $node->else->cond->args[1] instanceof \PhpParser\Node\Arg
            && $node->else->cond->args[1]->value instanceof \PhpParser\Node\Expr\Variable
            && $node->else->cond->args[1]->byRef === false
            && $node->else->cond->args[1]->unpack === false
            && $node->else->if instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $node->else->if->var instanceof \PhpParser\Node\Expr\Variable
            && $node->else->if->var->name === $node->else->cond->args[1]->value->name
            && $node->else->if->dim // TODO == $node->cond->left
            && $node->else->else instanceof \PhpParser\Node\Expr\Ternary
            && $node->else->else->cond instanceof \PhpParser\Node\Expr\BinaryOp\BooleanOr
            && $node->else->else->cond->left instanceof \PhpParser\Node\Expr\Assign
            && $node->else->else->cond->left->var instanceof \PhpParser\Node\Expr\Variable
            && $node->else->else->cond->left->var->name === 'v13'
            && $node->else->else->cond->left->expr instanceof \PhpParser\Node\Expr\FuncCall
            && $node->else->else->cond->left->expr->name instanceof \PhpParser\Node\Name
            && count($node->else->else->cond->left->expr->name->parts) === 1
            && $node->else->else->cond->left->expr->name->parts[0] === 'unpack'
            && count($node->else->else->cond->left->expr->args) === 2
            && $node->else->else->cond->left->expr->args[0] instanceof \PhpParser\Node\Arg
            && $node->else->else->cond->left->expr->args[0]->value instanceof \PhpParser\Node\Scalar\String_
            && $node->else->else->cond->left->expr->args[0]->value->value === 'c/N*'
            && $node->else->else->cond->left->expr->args[0]->byRef === false
            && $node->else->else->cond->left->expr->args[0]->unpack === false
            && $node->else->else->cond->left->expr->args[1] instanceof \PhpParser\Node\Arg
            && $node->else->else->cond->left->expr->args[1]->value // TODO == $node->cond->left
            && $node->else->else->cond->left->expr->args[1]->byRef === false
            && $node->else->else->cond->left->expr->args[1]->unpack === false
            && $node->else->else->cond->right instanceof \PhpParser\Node\Scalar\LNumber
            && $node->else->else->cond->right->value === 1
            && $node->else->else->if instanceof \PhpParser\Node\Expr\Ternary
            && $node->else->else->if->cond instanceof \PhpParser\Node\Expr\BinaryOp\BooleanOr
            && $node->else->else->if->cond->left instanceof \PhpParser\Node\Expr\Assign
            && $node->else->else->if->cond->left->var instanceof \PhpParser\Node\Expr\Variable
            && $node->else->else->if->cond->left->expr instanceof \PhpParser\Node\Expr\FuncCall
            && $node->else->else->if->cond->left->expr->name instanceof \PhpParser\Node\Name
            && count($node->else->else->if->cond->left->expr->name->parts) === 1
            && $node->else->else->if->cond->left->expr->name->parts[0] === 'substr'
            && count($node->else->else->if->cond->left->expr->args) === 3
            && $node->else->else->if->cond->left->expr->args[0] instanceof \PhpParser\Node\Arg
            && $node->else->else->if->cond->left->expr->args[0]->value instanceof \PhpParser\Node\Expr\FuncCall
            && $node->else->else->if->cond->left->expr->args[0]->value->name instanceof \PhpParser\Node\Name
            && count($node->else->else->if->cond->left->expr->args[0]->value->name->parts) === 1
            && $node->else->else->if->cond->left->expr->args[0]->value->name->parts[0] === 'func1'
            && count($node->else->else->if->cond->left->expr->args[0]->value->args) === 0
            && $node->else->else->if->cond->left->expr->args[0]->byRef === false
            && $node->else->else->if->cond->left->expr->args[0]->unpack === false
            && $node->else->else->if->cond->left->expr->args[1] instanceof \PhpParser\Node\Arg
            && $node->else->else->if->cond->left->expr->args[1]->value instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $node->else->else->if->cond->left->expr->args[1]->value->var instanceof \PhpParser\Node\Expr\Variable
            && $node->else->else->if->cond->left->expr->args[1]->value->var->name === $node->else->else->cond->left->var->name
            && $node->else->else->if->cond->left->expr->args[1]->value->dim instanceof \PhpParser\Node\Scalar\LNumber
            && $node->else->else->if->cond->left->expr->args[1]->value->dim->value === 1
            && $node->else->else->if->cond->left->expr->args[1]->byRef === false
            && $node->else->else->if->cond->left->expr->args[1]->unpack === false
            && $node->else->else->if->cond->left->expr->args[2] instanceof \PhpParser\Node\Arg
            && $node->else->else->if->cond->left->expr->args[2]->value instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $node->else->else->if->cond->left->expr->args[2]->value->var instanceof \PhpParser\Node\Expr\Variable
            && $node->else->else->if->cond->left->expr->args[2]->value->var->name === $node->else->else->cond->left->var->name
            && $node->else->else->if->cond->left->expr->args[2]->value->dim instanceof \PhpParser\Node\Scalar\LNumber
            && $node->else->else->if->cond->left->expr->args[2]->value->dim->value === 2
            && $node->else->else->if->cond->left->expr->args[2]->byRef === false
            && $node->else->else->if->cond->left->expr->args[2]->unpack === false
            && $node->else->else->if->cond->right instanceof \PhpParser\Node\Scalar\LNumber
            && $node->else->else->if->cond->right->value === 1
            && $node->else->else->if->if instanceof \PhpParser\Node\Expr\Ternary
            && $node->else->else->if->if->cond instanceof \PhpParser\Node\Expr\BinaryOp\BooleanAnd
            && $node->else->else->if->if->cond->left instanceof \PhpParser\Node\Expr\Assign
            && $node->else->else->if->if->cond->left->var instanceof \PhpParser\Node\Expr\Variable
            && $node->else->else->if->if->cond->left->expr instanceof \PhpParser\Node\Expr\ArrayDimFetch
            && $node->else->else->if->if->cond->left->expr->var instanceof \PhpParser\Node\Expr\Variable
            && $node->else->else->if->if->cond->left->expr->var->name === $node->else->else->if->cond->left->var->name
            && $node->else->else->if->if->cond->left->expr->dim instanceof \PhpParser\Node\Scalar\LNumber
            && $node->else->else->if->if->cond->left->expr->dim->value === 0
            && $node->else->else->if->if->cond->right instanceof \PhpParser\Node\Expr\BinaryOp\BooleanOr
            && $node->else->else->if->if->cond->right->left instanceof \PhpParser\Node\Expr\BinaryOp\Identical
            && $node->else->else->if->if->cond->right->left->left instanceof \PhpParser\Node\Expr\Variable
            && $node->else->else->if->if->cond->right->left->left->name === $node->else->else->if->if->cond->left->var->name
            && $node->else->else->if->if->cond->right->left->right instanceof \PhpParser\Node\Scalar\String_
            && $node->else->else->if->if->cond->right->left->right->value === "\x06"
            && $node->else->else->if->if->cond->right->right instanceof \PhpParser\Node\Expr\BinaryOp\Identical
            && $node->else->else->if->if->cond->right->right->left instanceof \PhpParser\Node\Expr\Variable
            && $node->else->else->if->if->cond->right->right->left->name === $node->else->else->if->if->cond->left->var->name
            && $node->else->else->if->if->cond->right->right->right instanceof \PhpParser\Node\Scalar\String_
            && $node->else->else->if->if->cond->right->right->right->value === "\x09"
            && $node->else->else->if->if->if instanceof \PhpParser\Node\Expr\Ternary
            && $node->else->else->if->if->if->cond instanceof \PhpParser\Node\Expr\BinaryOp\BooleanOr
            && $node->else->else->if->if->if->cond->left instanceof \PhpParser\Node\Expr\Assign
            && $node->else->else->if->if->if->cond->left->var instanceof \PhpParser\Node\Expr\Variable
            // && $node->else->else->if->if->if->cond->left->var->name === 'v24'
            && $node->else->else->if->if->if->cond->left->expr instanceof \PhpParser\Node\Expr\Variable
            && $node->else->else->if->if->if->cond->left->expr->name === $node->else->else->if->if->cond->left->expr->var->name
            && $node->else->else->if->if->if->cond->right instanceof \PhpParser\Node\Scalar\LNumber
            && $node->else->else->if->if->if->cond->right->value === 1
            && $node->else->else->if->if->if->if instanceof \PhpParser\Node\Expr\Eval_
            && $node->else->else->if->if->if->if->expr instanceof \PhpParser\Node\Expr\Variable
            // && $node->else->else->if->if->if->if->expr->name === 'v22'
            && $node->else->else->if->if->if->else instanceof \PhpParser\Node\Scalar\String_
            && $node->else->else->if->if->if->else->value === ''
            && $node->else->else->if->if->else instanceof \PhpParser\Node\Expr\Ternary
            && $node->else->else->if->if->else->cond instanceof \PhpParser\Node\Expr\BinaryOp\Identical
            && $node->else->else->if->if->else->cond->left instanceof \PhpParser\Node\Expr\Variable
            && $node->else->else->if->if->else->cond->left->name === $node->else->else->if->if->cond->left->var->name
            && $node->else->else->if->if->else->cond->right instanceof \PhpParser\Node\Scalar\String_
            && $node->else->else->if->if->else->cond->right->value === "\x04"
            && $node->else->else->if->if->else->if instanceof \PhpParser\Node\Expr\Cast\Int_
            && $node->else->else->if->if->else->if->expr instanceof \PhpParser\Node\Expr\FuncCall
            && $node->else->else->if->if->else->if->expr->name instanceof \PhpParser\Node\Name
            && count($node->else->else->if->if->else->if->expr->name->parts) === 1
            && $node->else->else->if->if->else->if->expr->name->parts[0] === 'substr'
            && count($node->else->else->if->if->else->if->expr->args) === 2
            && $node->else->else->if->if->else->if->expr->args[0] instanceof \PhpParser\Node\Arg
            && $node->else->else->if->if->else->if->expr->args[0]->value instanceof \PhpParser\Node\Expr\Variable
            && $node->else->else->if->if->else->if->expr->args[0]->value->name === $node->else->else->if->if->cond->left->expr->var->name
            && $node->else->else->if->if->else->if->expr->args[0]->byRef === false
            && $node->else->else->if->if->else->if->expr->args[0]->unpack === false
            && $node->else->else->if->if->else->if->expr->args[1] instanceof \PhpParser\Node\Arg
            && $node->else->else->if->if->else->if->expr->args[1]->value instanceof \PhpParser\Node\Scalar\LNumber
            && $node->else->else->if->if->else->if->expr->args[1]->value->value === 1
            && $node->else->else->if->if->else->if->expr->args[1]->byRef === false
            && $node->else->else->if->if->else->if->expr->args[1]->unpack === false
            && $node->else->else->if->if->else->else instanceof \PhpParser\Node\Expr\Ternary
            && $node->else->else->if->if->else->else->cond instanceof \PhpParser\Node\Expr\BinaryOp\Identical
            && $node->else->else->if->if->else->else->cond->left instanceof \PhpParser\Node\Expr\Variable
            && $node->else->else->if->if->else->else->cond->left->name === $node->else->else->if->if->cond->left->var->name
            && $node->else->else->if->if->else->else->cond->right instanceof \PhpParser\Node\Scalar\String_
            && $node->else->else->if->if->else->else->cond->right->value === "\x05"
            && $node->else->else->if->if->else->else->if instanceof \PhpParser\Node\Expr\Cast\Double
            && $node->else->else->if->if->else->else->if->expr instanceof \PhpParser\Node\Expr\FuncCall
            && $node->else->else->if->if->else->else->if->expr->name instanceof \PhpParser\Node\Name
            && count($node->else->else->if->if->else->else->if->expr->name->parts) === 1
            && $node->else->else->if->if->else->else->if->expr->name->parts[0] === 'substr'
            && count($node->else->else->if->if->else->else->if->expr->args) === 2
            && $node->else->else->if->if->else->else->if->expr->args[0] instanceof \PhpParser\Node\Arg
            && $node->else->else->if->if->else->else->if->expr->args[0]->value instanceof \PhpParser\Node\Expr\Variable
            && $node->else->else->if->if->else->else->if->expr->args[0]->value->name === $node->else->else->if->if->cond->left->expr->var->name
            && $node->else->else->if->if->else->else->if->expr->args[0]->byRef === false
            && $node->else->else->if->if->else->else->if->expr->args[0]->unpack === false
            && $node->else->else->if->if->else->else->if->expr->args[1] instanceof \PhpParser\Node\Arg
            && $node->else->else->if->if->else->else->if->expr->args[1]->value instanceof \PhpParser\Node\Scalar\LNumber
            && $node->else->else->if->if->else->else->if->expr->args[1]->value->value === 1
            && $node->else->else->if->if->else->else->if->expr->args[1]->byRef === false
            && $node->else->else->if->if->else->else->if->expr->args[1]->unpack === false
            && $node->else->else->if->if->else->else->else instanceof \PhpParser\Node\Expr\Ternary
            && $node->else->else->if->if->else->else->else->cond instanceof \PhpParser\Node\Expr\BinaryOp\Identical
            && $node->else->else->if->if->else->else->else->cond->left instanceof \PhpParser\Node\Expr\Variable
            && $node->else->else->if->if->else->else->else->cond->left->name === $node->else->else->if->if->cond->left->var->name
            && $node->else->else->if->if->else->else->else->cond->right instanceof \PhpParser\Node\Scalar\String_
            && $node->else->else->if->if->else->else->else->cond->right->value === "\x07"
            && $node->else->else->if->if->else->else->else->if instanceof \PhpParser\Node\Expr\ConstFetch
            && $node->else->else->if->if->else->else->else->if->name instanceof \PhpParser\Node\Name
            && count($node->else->else->if->if->else->else->else->if->name->parts) === 1
            && $node->else->else->if->if->else->else->else->if->name->parts[0] === 'true'
            && $node->else->else->if->if->else->else->else->else instanceof \PhpParser\Node\Expr\Ternary
            && $node->else->else->if->if->else->else->else->else->cond instanceof \PhpParser\Node\Expr\BinaryOp\Identical
            && $node->else->else->if->if->else->else->else->else->cond->left instanceof \PhpParser\Node\Expr\Variable
            && $node->else->else->if->if->else->else->else->else->cond->left->name === $node->else->else->if->if->cond->left->var->name
            && $node->else->else->if->if->else->else->else->else->cond->right instanceof \PhpParser\Node\Scalar\String_
            && $node->else->else->if->if->else->else->else->else->cond->right->value === "\x08"
            && $node->else->else->if->if->else->else->else->else->if instanceof \PhpParser\Node\Expr\ConstFetch
            && $node->else->else->if->if->else->else->else->else->if->name instanceof \PhpParser\Node\Name
            && count($node->else->else->if->if->else->else->else->else->if->name->parts) === 1
            && $node->else->else->if->if->else->else->else->else->if->name->parts[0] === 'false'
            && $node->else->else->if->if->else->else->else->else->else instanceof \PhpParser\Node\Expr\ConstFetch
            && $node->else->else->if->if->else->else->else->else->else->name instanceof \PhpParser\Node\Name
            && count($node->else->else->if->if->else->else->else->else->else->name->parts) === 1
            && $node->else->else->if->if->else->else->else->else->else->name->parts[0] === 'NULL'
            && $node->else->else->if->else instanceof \PhpParser\Node\Expr\ConstFetch
            && $node->else->else->if->else->name instanceof \PhpParser\Node\Name
            && count($node->else->else->if->else->name->parts) === 1
            && $node->else->else->if->else->name->parts[0] === 'NULL'
            && $node->else->else->else instanceof \PhpParser\Node\Expr\ConstFetch
            && $node->else->else->else->name instanceof \PhpParser\Node\Name
            && count($node->else->else->else->name->parts) === 1
            && $node->else->else->else->name->parts[0] === 'NULL') {
            return new Node\Expr\FuncCall(new Node\Name('get_value'), [
                new Node\Arg($node->cond->left),
            ]);
        }
    }
}

/**
 * Class ConditionExpressionNodeDumper
 *
 * Usage:
 *
 *     echo (new ConditionExpressionNodeDumper)->dump($ast);
 *     echo (new ConditionExpressionNodeDumper)->dump($node, '$node');
 *
 * @package Ganlv\EnphpDecoder\NodeDumpers
 */
class ConditionExpressionNodeDumper
{
    private $dumpPrefix = '';

    public function dump($node, $dumpPrefix = '$ast')
    {
        $this->dumpPrefix = $dumpPrefix;
        return $this->dumpRecursive($node);
    }

    protected function dumpRecursive($node)
    {
        $result = [];
        if ($node instanceof Node) {
            $result[] = $this->dumpPrefix . ' instanceof \\' . get_class($node);
            foreach ($node->getSubNodeNames() as $key) {
                $prefix = $this->dumpPrefix . '->' . $key;
                $value = $node->$key;
                if (is_null($value)) {
                    $result[] = $prefix . ' === null';
                } elseif (is_scalar($value)) {
                    if ('flags' === $key || 'newModifier' === $key) {
                        $result[] = $prefix . ' === ' . $this->dumpFlags($value);
                    } elseif ('type' === $key && $node instanceof Include_) {
                        $result[] = $prefix . ' === ' . $this->dumpIncludeType($value);
                    } elseif ('type' === $key
                        && ($node instanceof Use_ || $node instanceof UseUse || $node instanceof GroupUse)) {
                        $result[] = $prefix . ' === ' . $this->dumpUseType($value);
                    } else {
                        $result[] = $prefix . ' === ' . var_export($value, true);
                    }
                } else {
                    $oldPrefix = $this->dumpPrefix;
                    $this->dumpPrefix = $prefix;
                    $result = array_merge($result, [$this->dumpRecursive($value)]);
                    $this->dumpPrefix = $oldPrefix;
                }
            }
        } elseif (is_array($node)) {
            $result[] = 'count(' . $this->dumpPrefix . ') === ' . var_export(count($node), true);
            foreach ($node as $key => $value) {
                $prefix = $this->dumpPrefix . '[' . var_export($key, true) . ']';
                if (is_null($value)) {
                    $result[] = $prefix . ' === null';
                } elseif (is_scalar($value)) {
                    $result[] = $prefix . ' === ' . var_export($value, true);
                } else {
                    $oldPrefix = $this->dumpPrefix;
                    $this->dumpPrefix = $prefix;
                    $result = array_merge($result, [$this->dumpRecursive($value)]);
                    $this->dumpPrefix = $oldPrefix;
                }
            }
        } else {
            throw new \InvalidArgumentException('Can only dump nodes and arrays.');
        }

        return implode("\n&& ", $result);
    }

    protected function dumpFlags($flags)
    {
        $strs = [];
        if ($flags & Class_::MODIFIER_PUBLIC) {
            $strs[] = '\PhpParser\Node\Stmt\Class_::MODIFIER_PUBLIC';
        }
        if ($flags & Class_::MODIFIER_PROTECTED) {
            $strs[] = '\PhpParser\Node\Stmt\Class_::MODIFIER_PROTECTED';
        }
        if ($flags & Class_::MODIFIER_PRIVATE) {
            $strs[] = '\PhpParser\Node\Stmt\Class_::MODIFIER_PRIVATE';
        }
        if ($flags & Class_::MODIFIER_ABSTRACT) {
            $strs[] = '\PhpParser\Node\Stmt\Class_::MODIFIER_ABSTRACT';
        }
        if ($flags & Class_::MODIFIER_STATIC) {
            $strs[] = '\PhpParser\Node\Stmt\Class_::MODIFIER_STATIC';
        }
        if ($flags & Class_::MODIFIER_FINAL) {
            $strs[] = '\PhpParser\Node\Stmt\Class_::MODIFIER_FINAL';
        }

        if ($strs) {
            return implode(' | ', $strs);
        } else {
            return $flags;
        }
    }

    protected function dumpIncludeType($type)
    {
        $map = [
            Include_::TYPE_INCLUDE => '\PhpParser\Node\Expr\Include_::TYPE_INCLUDE',
            Include_::TYPE_INCLUDE_ONCE => '\PhpParser\Node\Expr\Include_::TYPE_INCLUDE_ONCE',
            Include_::TYPE_REQUIRE => '\PhpParser\Node\Expr\Include_::TYPE_REQUIRE',
            Include_::TYPE_REQUIRE_ONCE => '\PhpParser\Node\Expr\Include_::TYPE_REQUIRE_ONCE',
        ];
        if (!isset($map[$type])) {
            return $type;
        }
        return $map[$type];
    }

    protected function dumpUseType($type)
    {
        $map = [
            Use_::TYPE_UNKNOWN => '\PhpParser\Node\Stmt\Use_::TYPE_UNKNOWN',
            Use_::TYPE_NORMAL => '\PhpParser\Node\Stmt\Use_::TYPE_NORMAL',
            Use_::TYPE_FUNCTION => '\PhpParser\Node\Stmt\Use_::TYPE_FUNCTION',
            Use_::TYPE_CONSTANT => '\PhpParser\Node\Stmt\Use_::TYPE_CONSTANT',
        ];
        if (!isset($map[$type])) {
            return $type;
        }
        return $map[$type] . ' (' . $type . ')';
    }
}

$nodeVisitor = new FunctionLikeNodeVisitor(function ($node) {
    /** @var $node \PhpParser\Node\Stmt\Function_ */
    $nodeVisitor = new FunctionLocalVariableRenameNodeVisitor();
    $nodeVisitor2 = new FunctionStringReplaceNodeVisitor();
    $traverser = new NodeTraverser();
    $traverser->addVisitor($nodeVisitor);
    $traverser->addVisitor($nodeVisitor2);
    $ast = $traverser->traverse([$node]);
    file_put_contents('varMap.php', '<?php return ' . var_export($nodeVisitor->varMap, true) . ';');
    return $ast[0];
});
$traverser = new NodeTraverser();
$traverser->addVisitor($nodeVisitor);
$ast = $traverser->traverse($ast);

$nodeVisitor = new BeautifyNodeVisitor();
$traverser = new NodeTraverser();
$traverser->addVisitor($nodeVisitor);
$ast = $traverser->traverse($ast);

$nodeVisitor = new ReplaceGetValueNodeVisitor();
$traverser = new NodeTraverser();
$traverser->addVisitor($nodeVisitor);
$ast = $traverser->traverse($ast);

$prettyPrinter = new Standard;
$newCode = $prettyPrinter->prettyPrintFile($ast);

file_put_contents('tests/samples/t2_formatted.php_', $newCode);
