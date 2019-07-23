<?php

namespace Ganlv\Z5EncryptDecompiler;

use Ganlv\Z5EncryptDecompiler\NodeVisitors\FunctionFinderNodeVisitor;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Label;
use PhpParser\NodeTraverser;

class Disassembler3
{
    /** @var \Ganlv\Z5EncryptDecompiler\Disassembler2 */
    public $disassembler2;
    /** @var string */
    public $file;
    /** @var string */
    private $funcName;
    /** @var array */
    public $fileAst;
    /** @var \PhpParser\Node\Stmt\Function_ functionNode */
    public $functionNode;

    public function __construct(Disassembler2 $disassembler2, string $file, string $funcName)
    {
        $this->disassembler2 = $disassembler2;
        $this->file = $file;
        $this->funcName = $funcName;
        $this->fileAst = PhpParserHelper::parseFile($file);
    }

    public function findFunction()
    {
        $nodeTraverser = new NodeTraverser();
        $nodeVisitor = new FunctionFinderNodeVisitor($this->funcName);
        $nodeTraverser->addVisitor($nodeVisitor);
        $nodeTraverser->traverse($this->fileAst);
        return $nodeVisitor->functionNode;
    }

    public function disassemble(): string
    {
        $this->functionNode = $this->findFunction();
        if (!is_null($this->functionNode)) {
            return $this->decrypt();
        }
        return '';
    }

    public function decrypt(): string
    {
        $startLine = $this->functionNode->getAttribute('startLine');
        $endLine = $this->functionNode->getAttribute('endLine');
        $lines = explode("\n", $this->file);
        $functionLines = array_slice($lines, $startLine - 1, $endLine - $startLine + 1);
        $functionCode = trim(implode("\n", $functionLines));
        $keyCode = substr($functionCode, strpos($functionCode, '));'));
        $hashKey = $this->findHashKey('addr24');
        $key = str_rot13(hash_hmac('sha1', $keyCode, $hashKey));
        $data = $this->findEncryptedData('addr64');
        $iv = $this->findEncryptIv('addr43');
        $decrypted = openssl_decrypt($data, 'aes-256-cbc', $key, 1, $iv);
        return gzinflate($decrypted);
    }

    public function findHashKey($labelName)
    {
        foreach ($this->disassembler2->ast as $i => $node) {
            if ($node instanceof Label
                && $node->name instanceof Identifier
                && $node->name->name === $labelName) {
                /** @var \PhpParser\Node\Stmt\Expression */
                $nextNode = $this->disassembler2->ast[$i + 1];
                /** @var \PhpParser\Node\Expr\Assign $expr1 */
                $expr1 = $nextNode->expr;
                /** @var \PhpParser\Node\Scalar\String_ $expr2 */
                $expr2 = $expr1->expr;
                return $expr2->value;
            }
        }
        return '';
    }

    public function findEncryptedData($labelName)
    {
        foreach ($this->disassembler2->ast as $i => $node) {
            if ($node instanceof Label
                && $node->name instanceof Identifier
                && $node->name->name === $labelName) {
                /** @var \PhpParser\Node\Stmt\Expression */
                $nextNode = $this->disassembler2->ast[$i + 1];
                /** @var \PhpParser\Node\Expr\Assign $expr1 */
                $expr1 = $nextNode->expr;
                /** @var \PhpParser\Node\Scalar\String_ $expr2 */
                $expr2 = $expr1->expr;
                return $expr2->value;
            }
        }
        return '';
    }

    public function findEncryptIv($labelName)
    {
        foreach ($this->disassembler2->ast as $i => $node) {
            if ($node instanceof Label
                && $node->name instanceof Identifier
                && $node->name->name === $labelName) {
                /** @var \PhpParser\Node\Stmt\Expression */
                $nextNode = $this->disassembler2->ast[$i + 1];
                /** @var \PhpParser\Node\Expr\Assign $expr1 */
                $expr1 = $nextNode->expr;
                /** @var \PhpParser\Node\Scalar\String_ $expr2 */
                $expr2 = $expr1->expr;
                return $expr2->value;
            }
        }
        return '';
    }
}