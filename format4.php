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

$nodeVisitor = new FunctionLikeNodeVisitor(function ($node) {
    /** @var $node \PhpParser\Node\Stmt\Function_ */
    $nodeVisitor = new FunctionLocalVariableRenameNodeVisitor();
    $nodeVisitor2 = new FunctionStringReplaceNodeVisitor();
    $traverser = new NodeTraverser();
    $traverser->addVisitor($nodeVisitor);
    $traverser->addVisitor($nodeVisitor2);
    $ast = $traverser->traverse([$node]);
    return $ast[0];
});
$traverser = new NodeTraverser();
$traverser->addVisitor($nodeVisitor);
$ast = $traverser->traverse($ast);

$nodeVisitor2 = new BeautifyNodeVisitor();
$traverser = new NodeTraverser();
$traverser->addVisitor($nodeVisitor2);
$ast = $traverser->traverse($ast);

$prettyPrinter = new Standard;
$newCode = $prettyPrinter->prettyPrintFile($ast);

file_put_contents('tests/samples/t2_formatted.php_', $newCode);
