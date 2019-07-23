<?php

namespace Ganlv\Z5EncryptDecompiler;

use PhpParser\Node;

class Disassembler2
{
    /** @var \Ganlv\Z5EncryptDecompiler\Disassembler1 */
    public $disassembler1;
    /** @var int */
    public $pointer;
    /** @var array */
    public $visited;
    /** @var array */
    public $ast;
    /** @var bool */
    public $errorSuppress = false;
    /** @var array[] */
    public $funcCallStack;
    public $print = false;

    public function __construct(Disassembler1 $disassembler1)
    {
        $this->disassembler1 = $disassembler1;
        $this->ast = [];
    }

    public function disassemble()
    {
        $this->pointer = $this->disassembler1->entryPoint;
        $this->visited = [];
        while (array_key_exists($this->pointer, $this->disassembler1->instructions) && !in_array($this->pointer, $this->visited)) {
            $this->visited[] = $this->pointer;
            $instruction = $this->disassembler1->instructions[$this->pointer];
            $this->disassembleOne($instruction);
            $this->pointer = $instruction->next;
        }
    }

    public function disassembleOne(Instruction1 $instruction)
    {
        $this->ast[] = new Node\Stmt\Label(self::buildLabelName($instruction->index));
        $instruction->disassembler2 = $this;
        $stmts = $instruction->disassemble();
        if (!is_array($stmts)) {
            $stmts = [$stmts];
        }
        foreach ($stmts as $stmt) {
            if (!($stmt instanceof Node\Stmt\Nop)) {
                $this->ast[] = $stmt;
            }
        }
        if ($this->print) {
            echo PhpParserHelper::prettyPrint($this->ast), PHP_EOL;
        }
    }

    public static function buildRegisterNode(int $i)
    {
        return new Node\Expr\Variable('reg' . $i);
    }

    public static function buildLabelName($addr)
    {
        return 'addr' . $addr;
    }

    public function setErrorSuppress(bool $errorSuppress)
    {
        $this->errorSuppress = $errorSuppress;
    }

    public function isErrorSuppress(): bool
    {
        return $this->errorSuppress;
    }

    public function pushFuncCall(Node\Expr $funcNameNode)
    {
        $this->funcCallStack[] = [$funcNameNode, []];
    }

    public function pushMethodCall(Node\Expr $classNode, Node\Expr $funcNameNode)
    {
        $this->funcCallStack[] = [[$classNode, $funcNameNode], []];
    }

    public function pushNewCall(Node\Expr $classNameNode, int $registerNumber)
    {
        $this->funcCallStack[] = [$classNameNode, [], ['new', $registerNumber]];
    }

    public function pushFuncArg(Node\Expr $valueNode)
    {
        $this->funcCallStack[count($this->funcCallStack) - 1][1][] = $valueNode;
    }

    public function popFunctionLikeCall(): array
    {
        return array_pop($this->funcCallStack);
    }
}