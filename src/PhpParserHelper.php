<?php

namespace Ganlv\Z5EncryptDecompiler;

use Ganlv\Z5EncryptDecompiler\NodeVisitors\BeautifyNodeVisitor;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class PhpParserHelper
{
    public static function beautify(array $ast): array
    {
        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new BeautifyNodeVisitor());
        $ast = $nodeTraverser->traverse($ast);
        return $ast;
    }

    public static function prettyPrinter(): Standard
    {
        static $printer = null;
        if (is_null($printer)) {
            $printer = new Standard();
        }
        return $printer;
    }

    public static function prettyPrintFile(array $ast): string
    {
        return self::prettyPrinter()->prettyPrintFile($ast);
    }

    public static function prettyPrint(array $ast): string
    {
        return self::prettyPrinter()->prettyPrint($ast);
    }

    public static function prettyPrintExpr(array $node): string
    {
        return self::prettyPrinter()->prettyPrintExpr($node);
    }

    public static function parser(): Parser
    {
        static $parser = null;
        if (is_null($parser)) {
            $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        }
        return $parser;
    }

    public static function parseFile(string $code): array
    {
        return self::parser()->parse($code);
    }

    public static function parseExpr(string $code): Node\Expr
    {
        $ast = self::parser()->parse('<?php ' . $code . ';');
        assert(count($ast) === 1);
        assert($ast[0] instanceof Node\Stmt\Expression);
        return $ast[0]->expr;
    }
}