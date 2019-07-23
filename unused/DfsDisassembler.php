<?php

namespace Ganlv\Z5EncryptDecompiler;

use PhpParser\Node;

class DfsDisassembler
{
    /** @var \Ganlv\Z5EncryptDecompiler\Disassembler */
    public $disassembler1 = null;
    public $visited = [];
    public $pointer = 0;
    public $ast = [];
    public $funcStack = [];
    public $funcStackLen = 0;
    public $isSuppressError = false;

    public static function buildFunctionName($name)
    {
        if (is_array($name)) {
            return $name[0] . '->' . $name[1];
        }
        return $name;
    }

    public function disassemble()
    {
        while (array_key_exists($this->pointer, $this->disassembler1->instructions) && !in_array($this->pointer, $this->visited)) {
            $this->visited[] = $this->pointer;
            /** @var \Ganlv\Z5EncryptDecompiler\Instruction $instruction */
            $instruction = $this->disassembler1->instructions[$this->pointer];
            $this->disassembleOne($instruction);
            $this->pointer = $instruction->next;
        }
    }

    public function disassembleOne(Instruction $instruction)
    {
        $this->ast[] = new Node\Stmt\Label(self::getLabelName($instruction->index));
        $stmt = $this->disassemblerInstruction($instruction);
        if (!($stmt instanceof Node\Stmt\Nop)) {
            $this->ast[] = $stmt;
        }
    }

    public static function getLabelName(int $addr)
    {
        return 'addr' . $addr;
    }

    public function disassemblerInstruction(Instruction $instruction): Node\Stmt
    {
        switch ($instruction->type) {
            // Const ====================
            // __SHAREVM_FUNCTION__
            case 0x1a67:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        self::getRegisterNode($instruction->getArg(1)),
                        new Node\Expr\ConstFetch(new Node\Name('__SHAREVM_FUNCTION__'))
                    )
                );
            // __CLASS__
            case 0x1384:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        self::getRegisterNode($instruction->getArg(1)),
                        new Node\Expr\ConstFetch(new Node\Name('__CLASS__'))
                    )
                );
            // __FUNCTION__
            case 0x1ec1:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        self::getRegisterNode($instruction->getArg(1)),
                        new Node\Expr\ConstFetch(new Node\Name('__FUNCTION__'))
                    )
                );

            // Assign Expression ====================
            // =
            case 0xb2a:
                if ($instruction->getArgCount() > 4) {
                    return new Node\Stmt\Expression(
                        new Node\Expr\Assign(
                            self::getRegisterNode($instruction->getArg(1)),
                            new Node\Expr\Assign(
                                self::getRegisterNode($instruction->getArg(2)),
                                $this->getValueNode($instruction->getArg(3))
                            )
                        )
                    );
                } else {
                    return new Node\Stmt\Expression(
                        new Node\Expr\Assign(
                            self::getRegisterNode($instruction->getArg(1)),
                            $this->getValueNode($instruction->getArg(2))
                        )
                    );
                }
            // =& ${}
            case 0x1110:
                return new Node\Stmt\Expression(
                    new Node\Expr\AssignRef(
                        self::getRegisterNode($instruction->getArg(1)),
                        new Node\Expr\Variable(
                            $this->getValueNode($instruction->getArg(2))
                        )
                    )
                );
            // +
            case 0x1783:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        self::getRegisterNode($instruction->getArg(1)),
                        new Node\Expr\BinaryOp\Plus(
                            $this->getValueNode($instruction->getArg(2)),
                            $this->getValueNode($instruction->getArg(3))
                        )
                    )
                );
            // -
            case 0x1250:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        self::getRegisterNode($instruction->getArg(1)),
                        new Node\Expr\BinaryOp\Minus(
                            $this->getValueNode($instruction->getArg(2)),
                            $this->getValueNode($instruction->getArg(3))
                        )
                    )
                );
            // *
            case 0xb6f:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        self::getRegisterNode($instruction->getArg(1)),
                        new Node\Expr\BinaryOp\Mul(
                            $this->getValueNode($instruction->getArg(2)),
                            $this->getValueNode($instruction->getArg(3))
                        )
                    )
                );
            // -=
            case 0x514:
                return new Node\Stmt\Expression(
                    new Node\Expr\AssignOp\Minus(
                        self::getRegisterNode($instruction->getArg(1)),
                        $this->getValueNode($instruction->getArg(2))
                    )
                );
            // <
            case 0x6f9:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        self::getRegisterNode($instruction->getArg(1)),
                        new Node\Expr\BinaryOp\Smaller(
                            $this->getValueNode($instruction->getArg(2)),
                            $this->getValueNode($instruction->getArg(3))
                        )
                    )
                );
            // ==
            case 0xa33:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        self::getRegisterNode($instruction->getArg(1)),
                        new Node\Expr\BinaryOp\Equal(
                            $this->getValueNode($instruction->getArg(2)),
                            $this->getValueNode($instruction->getArg(3))
                        )
                    )
                );
            // ===
            case 0x13c5:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        self::getRegisterNode($instruction->getArg(1)),
                        new Node\Expr\BinaryOp\Identical(
                            $this->getValueNode($instruction->getArg(2)),
                            $this->getValueNode($instruction->getArg(3))
                        )
                    )
                );
            // !==
            case 0x12c2:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        self::getRegisterNode($instruction->getArg(1)),
                        new Node\Expr\BinaryOp\NotIdentical(
                            $this->getValueNode($instruction->getArg(2)),
                            $this->getValueNode($instruction->getArg(3))
                        )
                    )
                );
            // count
            case 0x189e:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        self::getRegisterNode($instruction->getArg(1)),
                        new Node\Expr\FuncCall(
                            new Node\Name('count'),
                            [
                                new Node\Arg(self::getRegisterNode($instruction->getArg(2))),
                            ]
                        )
                    )
                );
            // is_*
            case 0x14b9:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        self::getRegisterNode($instruction->getArg(1)),
                        new Node\Expr\FuncCall(
                            new Node\Name($this->getIsX($instruction->getArg(4))),
                            [
                                new Node\Arg(self::getRegisterNode($instruction->getArg(2))),
                            ]
                        )
                    )
                );
            // eval include_once include
            case 0x1b2e:
                if ($instruction->getArg(3) !== '') {
                    $type = $instruction->getArg(3);
                    $valueNode = $this->getValueNode($instruction->getArg(2));
                } else {
                    $type = $instruction->getArg(2);
                    $valueNode = $this->getValueNode($instruction->getArg(1));
                }
                if ($type === 'e') {
                    $expr = new Node\Expr\Eval_($valueNode);
                } else {
                    if ($type === 'i') {
                        $expr = new Node\Expr\Include_($valueNode, Node\Expr\Include_::TYPE_INCLUDE_ONCE);
                    } else {
                        $expr = new Node\Expr\Include_($valueNode, Node\Expr\Include_::TYPE_INCLUDE);
                    }
                }
                if ($instruction->getArg(3) !== '') {
                    return new Node\Stmt\Expression(
                        new Node\Expr\Assign(
                            self::getRegisterNode($instruction->getArg(1)),
                            $expr
                        )
                    );
                }
                return new Node\Stmt\Expression($expr);

            // Stmt ====================
            // return
            case 0x1d89:
                return new Node\Stmt\Return_($this->getValueNode($instruction->getArg(1)));
            // exit
            case 0x790:
                return new Node\Stmt\Expression(
                    new Node\Expr\Exit_($this->getValueNode($instruction->getArg(1)))
                );
            // echo
            case 0x16a8:
                return new Node\Stmt\Echo_([
                    $this->getValueNode($instruction->getArg(1)),
                ]);
            // unset
            case 0x16fd:
                return new Node\Stmt\Unset_([
                    $this->getValueNode($instruction->getArg(1)),
                ]);

            // FuncLike ====================
            // +@
            case 0x936:
                $this->setSuppressError(true);
                return new Node\Stmt\Nop();
            // -@
            case 0x72c:
                $this->setSuppressError(false);
                return new Node\Stmt\Nop();
            // buildFuncCall
            case 0x784:
                $this->buildFuncCall($this->getValueNode($instruction->getArg(2)));
                return new Node\Stmt\Nop();
            // buildFuncCall
            case 0x11af:
                $this->buildFuncCall($this->getValueNode($instruction->getArg(1)));
                return new Node\Stmt\Nop();
            // buildMethodCall
            case 0x1a27:
                if ($instruction->getArg(1) === "") {
                    $class = new Node\Expr\Variable('this');
                } else {
                    $class = $this->getValueNode($instruction->getArg(1));
                }
                $this->buildMethodCall($class, $this->getValueNode($instruction->getArg(2)));
                return new Node\Stmt\Nop();
            // buildNewCall
            case 0x602:
                $valueNode = $this->getValueNode($instruction->getArg(2));
                if ($valueNode instanceof Node\Scalar\String_ && $valueNode->value === '') {
                    $this->buildNewCall(
                        new Node\Expr\ConstFetch(new Node\Name('__CLASS__')),
                        self::getRegisterNode($instruction->getArg(1))
                    );
                }
                $this->buildNewCall($valueNode, self::getRegisterNode($instruction->getArg(1)));
                $nop = new Node\Stmt\Nop();
                // TODO
                // $nop->setAttribute('comments', [
                //     new Comment('// build new call'),
                // ]);
                return $nop;
            // push arg
            case 0x930:
                $this->pushFuncArg($this->getValueNode($instruction->getArg(1)));
                return new Node\Stmt\Nop();
            // push arg
            case 0xfe6:
                $this->pushFuncArg($this->getValueNode($instruction->getArg(1)));
                return new Node\Stmt\Nop();
            // push arg
            case 0x1ca8:
                $this->pushFuncArg($this->getValueNode($instruction->getArg(1)));
                return new Node\Stmt\Nop();
            // (bool)
            case 0xfea:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        self::getRegisterNode($instruction->getArg(1)),
                        new Node\Expr\Cast\Bool_(
                            $this->getValueNode($instruction->getArg(2))
                        )
                    )
                );
            // !(bool)
            case 0x1207:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        self::getRegisterNode($instruction->getArg(1)),
                        new Node\Expr\BooleanNot(
                            new Node\Expr\Cast\Bool_(
                                $this->getValueNode($instruction->getArg(2))
                            )
                        )
                    )
                );
            // method call
            case 0xce1:
                $funcCall = $this->popFuncCall();
                if (is_array($funcCall[0])) {
                    $expr = new Node\Expr\MethodCall(
                        $funcCall[0][0],
                        $funcCall[0][1],
                        $funcCall[1]
                    );
                } else {
                    $expr = new Node\Expr\FuncCall(
                        $funcCall[0],
                        $funcCall[1]
                    );
                }
                if ($this->isSuppressError()) {
                    $expr = new Node\Expr\ErrorSuppress(
                        $expr
                    );
                }
                if ($instruction->getArg(1) !== '') {
                    return new Node\Stmt\Expression(
                        new Node\Expr\Assign(
                            self::getRegisterNode($instruction->getArg(1)),
                            $expr
                        )
                    );
                }
                return new Node\Stmt\Expression(
                    $expr
                );
            // new call / function call / method call
            case 0x157d:
                $funcCall = $this->popFuncCall();
                if (isset($funcCall[2])) {
                    if ($funcCall[2][0] === 'new') {
                        $expr = new Node\Expr\New_(
                            $funcCall[0],
                            $funcCall[1]
                        );
                        if ($this->isSuppressError()) {
                            $expr = new Node\Expr\ErrorSuppress($expr);
                        }
                        return new Node\Stmt\Expression(
                            new Node\Expr\Assign(
                                $funcCall[2][1],
                                $expr
                            )
                        );
                    }
                } else {
                    if (is_array($funcCall[0])) {
                        $expr = new Node\Expr\MethodCall(
                            $funcCall[0][0],
                            $funcCall[0][1],
                            $funcCall[1]
                        );
                    } else {
                        $expr = new Node\Expr\FuncCall(
                            $funcCall[0],
                            $funcCall[1]
                        );
                    }
                    if ($this->isSuppressError()) {
                        $expr = new Node\Expr\ErrorSuppress($expr);
                    }
                    if ($instruction->getArg(1) !== '') {
                        return new Node\Stmt\Expression(
                            new Node\Expr\Assign(
                                self::getRegisterNode($instruction->getArg(1)),
                                $expr
                            )
                        );
                    }
                    return new Node\Stmt\Expression(
                        $expr
                    );
                }
                throw new \Exception('unreachable');

            // Jump ====================
            // if ((*1)) { goto addr((*2) - 1); }
            case 0xac3:
                $if = new Node\Stmt\If_($this->getValueNode($instruction->getArg(1)));
                $valueNode = $this->getValueNode($instruction->getArg(2));
                if (!($valueNode instanceof Node\Scalar\LNumber)) {
                    throw new \Exception('must goto LNumber');
                }
                $if->stmts = [
                    new Node\Stmt\Goto_(self::getLabelName($valueNode->value - 1)),
                ];
                return $if;
            // if ((*1)) { goto addr((*2) - 1); } else { goto addr((*3) - 1); }
            case 0x12ba:
                $if = new Node\Stmt\If_(self::getRegisterNode($instruction->getArg(1)));
                $valueNode = $this->getValueNode($instruction->getArg(2));
                if (!($valueNode instanceof Node\Scalar\LNumber)) {
                    throw new \Exception('must goto LNumber');
                }
                $if->stmts = [
                    new Node\Stmt\Goto_(self::getLabelName($valueNode->value - 1)),
                ];
                $valueNode = $this->getValueNode($instruction->getArg(3));
                if (!($valueNode instanceof Node\Scalar\LNumber)) {
                    throw new \Exception('must goto LNumber');
                }
                $if->else = new Node\Stmt\Else_([
                    new Node\Stmt\Goto_(self::getLabelName($valueNode->value - 1)),
                ]);
                return $if;
            // if (!(*1)) { goto addr((*2) - 1); }
            case 0x1c37:
                $if = new Node\Stmt\If_(
                    new Node\Expr\BooleanNot(
                        $this->getValueNode($instruction->getArg(1))
                    )
                );
                $valueNode = $this->getValueNode($instruction->getArg(2));
                if (!($valueNode instanceof Node\Scalar\LNumber)) {
                    throw new \Exception('must goto LNumber');
                }
                $if->stmts = [
                    new Node\Stmt\Goto_(self::getLabelName($valueNode->value - 1)),
                ];
                return $if;

            // Nop ====================
            case 0x561:
                return new Node\Stmt\Nop();
            case 0xedc:
                return new Node\Stmt\Nop();
            case 0x614:
                return new Node\Stmt\Nop();
        }
    }

    public static function getRegisterNode(int $i)
    {
        return new Node\Expr\Variable('reg' . $i);
    }

    public function getValueNode($key): Node\Expr
    {
        if ($key === '') {
            return new Node\Scalar\String_('');
        } elseif (strlen($key < 9) && is_numeric($key)) {
            return self::getRegisterNode($key);
        } else {
            $pos = unpack('c/N*', $key);
            $data = substr($this->disassembler1->data, $pos[1], $pos[2]);
            $type = ord($data[0]);
            switch ($type) {
                case 6:
                case 9:
                    $v23 = substr($data, 1);
                    if ($v23[0] == 0) {
                        return new Node\Scalar\String_(substr($v23, 1));
                    }
                    $v25 = intval($v23[0]);
                    $v26 = substr($v23, 0, $v25 + 1);
                    $v27 = substr($v23, $v25 + 1);
                    $v28 = openssl_decrypt($v27, 'AES-128-ECB', $v26, OPENSSL_RAW_DATA);
                    if ($type === 6) {
                        return new Node\Scalar\String_($v28);
                    } else {
                        $ast = PhpParserHelper::parser()->parse('<?php ' . $v28 . ';');
                        assert(count($ast) === 1);
                        $node = $ast[0];
                        assert($node instanceof Node\Stmt\Expression);
                        return $node->expr;
                    }
                    break;
                case 4:
                    return new Node\Scalar\LNumber((int)substr($data, 1));
                case 5:
                    return new Node\Scalar\DNumber((double)substr($data, 1));
                case 7:
                    return new Node\Expr\ConstFetch(new Node\Name('true'));
                case 8:
                    return new Node\Expr\ConstFetch(new Node\Name('false'));
                default:
                    return new Node\Expr\ConstFetch(new Node\Name('null'));
            }
        }
    }

    public function getIsX($key)
    {
        $v9 = ['undef', 'null', 'false', 'true', 'int', 'double', 'string', 'array', 'object', 'resource', 'reference', 'constant', null, 'bool', 'callable', 'indirect', null, 'ptr', 'void', 'iterable', 'error'];
        return 'is_' . $v9[substr($key, 2)];
    }

    public function setSuppressError($value)
    {
        $this->isSuppressError = $value;
    }

    public function buildFuncCall($func_name)
    {
        $this->funcStack[] = [$func_name, []];
        $this->funcStackLen++;
    }

    public function buildMethodCall($class, $func_name)
    {
        $this->funcStack[] = [[$class, $func_name], []];
        $this->funcStackLen++;
    }

    public function buildNewCall($func_name, $newRegister)
    {
        $this->funcStack[] = [$func_name, [], ['new', $newRegister]];
        $this->funcStackLen++;
    }

    public function pushFuncArg($value)
    {
        $this->funcStack[$this->funcStackLen - 1][1][] = new Node\Arg($value);
    }

    public function popFuncCall()
    {
        $this->funcStackLen--;
        return array_pop($this->funcStack);
    }

    /**
     * @return bool
     */
    public function isSuppressError(): bool
    {
        return $this->isSuppressError;
    }
}