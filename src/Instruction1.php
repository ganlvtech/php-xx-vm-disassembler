<?php

namespace Ganlv\Z5EncryptDecompiler;

use Exception;
use PhpParser\Node;

class Instruction1
{
    public $index = 0;
    public $type = 0;
    public $next = 0;
    public $args = [];
    /** @var \Ganlv\Z5EncryptDecompiler\Disassembler1 */
    public $disassembler1;
    /** @var \Ganlv\Z5EncryptDecompiler\Disassembler2 */
    public $disassembler2;

    public function __construct(int $index, int $type, int $next, array $args, Disassembler1 $disassembler1)
    {
        $this->index = $index;
        $this->type = $type;
        $this->next = $next;
        $this->args = $args;
        $this->disassembler1 = $disassembler1;
    }

    public function disassemble()
    {
        switch ($this->type) {
            // Const ====================
            // __SHAREVM_FUNCTION__
            case 0x1a67:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        $this->getRegisterNode(1),
                        new Node\Expr\ConstFetch(new Node\Name('__SHAREVM_FUNCTION__'))
                    )
                );
            // __CLASS__
            case 0x1384:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        $this->getRegisterNode(1),
                        new Node\Expr\ConstFetch(new Node\Name('__CLASS__'))
                    )
                );
            // __FUNCTION__
            case 0x1ec1:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        $this->getRegisterNode(1),
                        new Node\Expr\ConstFetch(new Node\Name('__FUNCTION__'))
                    )
                );

            // Assign Expression ====================
            // =
            case 0xb2a:
                if ($this->getArgCount() > 4) {
                    return new Node\Stmt\Expression(
                        new Node\Expr\Assign(
                            $this->getRegisterNode(1),
                            new Node\Expr\Assign(
                                $this->getRegisterNode(2),
                                $this->getValueNode(3)
                            )
                        )
                    );
                } else {
                    return new Node\Stmt\Expression(
                        new Node\Expr\Assign(
                            $this->getRegisterNode(1),
                            $this->getValueNode(2)
                        )
                    );
                }
            // =& ${}
            case 0x1110:
                return new Node\Stmt\Expression(
                    new Node\Expr\AssignRef(
                        $this->getRegisterNode(1),
                        new Node\Expr\Variable(
                            $this->getValueNode(2)
                        )
                    )
                );
            // +
            case 0x1783:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        $this->getRegisterNode(1),
                        new Node\Expr\BinaryOp\Plus(
                            $this->getValueNode(2),
                            $this->getValueNode(3)
                        )
                    )
                );
            // -
            case 0x1250:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        $this->getRegisterNode(1),
                        new Node\Expr\BinaryOp\Minus(
                            $this->getValueNode(2),
                            $this->getValueNode(3)
                        )
                    )
                );
            // *
            case 0xb6f:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        $this->getRegisterNode(1),
                        new Node\Expr\BinaryOp\Mul(
                            $this->getValueNode(2),
                            $this->getValueNode(3)
                        )
                    )
                );
            // -=
            case 0x514:
                return new Node\Stmt\Expression(
                    new Node\Expr\AssignOp\Minus(
                        $this->getRegisterNode(1),
                        $this->getValueNode(2)
                    )
                );
            // <
            case 0x6f9:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        $this->getRegisterNode(1),
                        new Node\Expr\BinaryOp\Smaller(
                            $this->getValueNode(2),
                            $this->getValueNode(3)
                        )
                    )
                );
            // ==
            case 0xa33:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        $this->getRegisterNode(1),
                        new Node\Expr\BinaryOp\Equal(
                            $this->getValueNode(2),
                            $this->getValueNode(3)
                        )
                    )
                );
            // ===
            case 0x13c5:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        $this->getRegisterNode(1),
                        new Node\Expr\BinaryOp\Identical(
                            $this->getValueNode(2),
                            $this->getValueNode(3)
                        )
                    )
                );
            // !==
            case 0x12c2:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        $this->getRegisterNode(1),
                        new Node\Expr\BinaryOp\NotIdentical(
                            $this->getValueNode(2),
                            $this->getValueNode(3)
                        )
                    )
                );
            // (bool)
            case 0xfea:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        $this->getRegisterNode(1),
                        new Node\Expr\Cast\Bool_(
                            $this->getValueNode(2)
                        )
                    )
                );
            // !(bool)
            case 0x1207:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        $this->getRegisterNode(1),
                        new Node\Expr\BooleanNot(
                            new Node\Expr\Cast\Bool_(
                                $this->getValueNode(2)
                            )
                        )
                    )
                );
            // count
            case 0x189e:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        $this->getRegisterNode(1),
                        new Node\Expr\FuncCall(
                            new Node\Name('count'),
                            [
                                new Node\Arg($this->getRegisterNode(2)),
                            ]
                        )
                    )
                );
            // is_*
            case 0x14b9:
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        $this->getRegisterNode(1),
                        new Node\Expr\FuncCall(
                            new Node\Name($this->getIsXFunctionName(4)),
                            [
                                new Node\Arg($this->getRegisterNode(2)),
                            ]
                        )
                    )
                );
            // eval include_once include
            case 0x1b2e:
                if ($this->getArg(3) !== '') {
                    $type = $this->getArg(3);
                    $valueNode = $this->getValueNode(2);
                } else {
                    $type = $this->getArg(2);
                    $valueNode = $this->getValueNode(1);
                }
                if ($type === 'e') {
                    $expr = new Node\Expr\Eval_($valueNode);
                } elseif ($type === 'i') {
                    $expr = new Node\Expr\Include_($valueNode, Node\Expr\Include_::TYPE_INCLUDE_ONCE);
                } else {
                    $expr = new Node\Expr\Include_($valueNode, Node\Expr\Include_::TYPE_INCLUDE);
                }
                if ($this->getArg(3) !== '') {
                    return new Node\Stmt\Expression(
                        new Node\Expr\Assign(
                            $this->getRegisterNode(1),
                            $expr
                        )
                    );
                } else {
                    return new Node\Stmt\Expression($expr);
                }

            // Stmt ====================
            // return
            case 0x1d89:
                return new Node\Stmt\Return_($this->getValueNode(1));
            // exit
            case 0x790:
                return new Node\Stmt\Expression(
                    new Node\Expr\Exit_($this->getValueNode(1))
                );
            // echo
            case 0x16a8:
                return new Node\Stmt\Echo_([
                    $this->getValueNode(1),
                ]);
            // unset
            case 0x16fd:
                return new Node\Stmt\Unset_([
                    $this->getValueNode(1),
                ]);

            // FuncLike ====================
            // +@
            case 0x936:
                $this->disassembler2->setErrorSuppress(true);
                return new Node\Stmt\Nop();
            // -@
            case 0x72c:
                $this->disassembler2->setErrorSuppress(false);
                return new Node\Stmt\Nop();
            // pushFuncCall
            case 0x784:
                $this->disassembler2->pushFuncCall($this->getValueNode(2));
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        new Node\Expr\ArrayDimFetch(
                            new Node\Expr\Variable('funcStack'),
                            null
                        ),
                        new Node\Expr\Array_([
                            $this->getValueNode(2),
                            new Node\Expr\Array_([]),
                        ])
                    )
                );
            // pushFuncCall
            case 0x11af:
                $this->disassembler2->pushFuncCall($this->getValueNode(1));
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        new Node\Expr\ArrayDimFetch(
                            new Node\Expr\Variable('funcStack'),
                            null
                        ),
                        new Node\Expr\Array_([
                            $this->getValueNode(1),
                            new Node\Expr\Array_([]),
                        ])
                    )
                );
            // pushMethodCall
            case 0x1a27:
                if ($this->getArg(1) === '') {
                    $class = new Node\Expr\Variable('this');
                } else {
                    $class = $this->getValueNode(1);
                }
                $this->disassembler2->pushMethodCall($class, $this->getValueNode(2));
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        new Node\Expr\ArrayDimFetch(
                            new Node\Expr\Variable('funcStack'),
                            null
                        ),
                        new Node\Expr\Array_([
                            new Node\Expr\Array_([
                                $class,
                                $this->getValueNode(2),
                            ]),
                            new Node\Expr\Array_([]),
                        ])
                    )
                );
            // pushNewCall
            case 0x602:
                $valueNode = $this->getValueNode(2);
                if ($valueNode instanceof Node\Scalar\String_) {
                    if ($valueNode->value === '') {
                        $valueNode = new Node\Expr\ConstFetch(new Node\Name('__CLASS__'));
                    }
                } else {
                    throw new Exception('new class name not string');
                }
                $this->disassembler2->pushNewCall(
                    $valueNode,
                    $this->getArg(1)
                );
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        new Node\Expr\ArrayDimFetch(
                            new Node\Expr\Variable('funcStack'),
                            null
                        ),
                        new Node\Expr\Array_([
                            $valueNode,
                            new Node\Expr\Array_([]),
                            new Node\Expr\Array_([
                                new Node\Scalar\String_('new'),
                                new Node\Scalar\LNumber($this->getArg(1)),
                            ]),
                        ])
                    )
                );
            // push arg
            case 0x930:
            case 0xfe6:
            case 0x1ca8:
                $this->disassembler2->pushFuncArg($this->getValueNode(1));
                return new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        new Node\Expr\ArrayDimFetch(
                            new Node\Expr\ArrayDimFetch(
                                new Node\Expr\ArrayDimFetch(
                                    new Node\Expr\Variable('funcStack'),
                                    new Node\Expr\BinaryOp\Minus(
                                        new Node\Expr\FuncCall(
                                            new Node\Name('count'),
                                            [
                                                new Node\Expr\Variable('funcStack'),
                                            ]
                                        ),
                                        new Node\Scalar\LNumber(1)
                                    )
                                ),
                                new Node\Scalar\LNumber(1)
                            ),
                            null
                        ),
                        $this->getValueNode(1)
                    )
                );
            // function call / method call
            case 0xce1:
                /** @var array $funcCallTemp unused variable */
                $funcCallTemp = $this->disassembler2->popFunctionLikeCall();
                $arrayPopExpression = new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        new Node\Expr\Variable('funcCallTemp'),
                        new Node\Expr\FuncCall(
                            new Node\Name('array_pop'),
                            [
                                new Node\Arg(new Node\Expr\Variable('funcStack')),
                            ]
                        )
                    )
                );
                $expr = new Node\Expr\FuncCall(
                    new Node\Expr\ArrayDimFetch(
                        new Node\Expr\Variable('funcCallTemp'),
                        new Node\Scalar\LNumber(0)
                    ),
                    [
                        new Node\Arg(
                            new Node\Expr\ArrayDimFetch(
                                new Node\Expr\Variable('funcCallTemp'),
                                new Node\Scalar\LNumber(1)
                            ),
                            false,
                            true
                        ),
                    ]
                );
                if ($this->disassembler2->isErrorSuppress()) {
                    $expr = new Node\Expr\ErrorSuppress(
                        $expr
                    );
                }
                if ($this->getArg(1) !== '') {
                    $expr = new Node\Expr\Assign(
                        $this->getRegisterNode(1),
                        $expr
                    );
                }
                return [
                    $arrayPopExpression,
                    new Node\Stmt\Expression($expr),
                ];
            // new call / function call / method call
            case 0x157d:
                $funcCallTemp = $this->disassembler2->popFunctionLikeCall();
                if (isset($funcCallTemp[2])) {
                    if ($funcCallTemp[2][0] === 'new') {
                        $arrayPopExpression = new Node\Stmt\Expression(
                            new Node\Expr\Assign(
                                new Node\Expr\Variable('funcCallTemp'),
                                new Node\Expr\FuncCall(
                                    new Node\Name('array_pop'),
                                    [
                                        new Node\Arg(new Node\Expr\Variable('funcStack')),
                                    ]
                                )
                            )
                        );
                        $expr = new Node\Expr\New_(
                            new Node\Expr\ArrayDimFetch(
                                new Node\Expr\Variable('funcCallTemp'),
                                new Node\Scalar\LNumber(0)
                            ),
                            [
                                new Node\Arg(
                                    new Node\Expr\ArrayDimFetch(
                                        new Node\Expr\Variable('funcCallTemp'),
                                        new Node\Scalar\LNumber(1)
                                    ),
                                    false,
                                    true
                                ),
                            ]
                        );
                        if ($this->disassembler2->isErrorSuppress()) {
                            $expr = new Node\Expr\ErrorSuppress(
                                $expr
                            );
                        }
                        $newExpression = new Node\Stmt\Expression(
                            new Node\Expr\Assign(
                                Disassembler2::buildRegisterNode($funcCallTemp[2][1]),
                                $expr
                            )
                        );
                        return [
                            $arrayPopExpression,
                            $newExpression,
                        ];
                    } else {
                        throw new Exception('has $funcCallTemp[2] but not new');
                    }
                } else {
                    $arrayPopExpression = new Node\Stmt\Expression(
                        new Node\Expr\Assign(
                            new Node\Expr\Variable('funcCallTemp'),
                            new Node\Expr\FuncCall(
                                new Node\Name('array_pop'),
                                [
                                    new Node\Arg(new Node\Expr\Variable('funcStack')),
                                ]
                            )
                        )
                    );
                    $expr = new Node\Expr\FuncCall(
                        new Node\Expr\ArrayDimFetch(
                            new Node\Expr\Variable('funcCallTemp'),
                            new Node\Scalar\LNumber(0)
                        ),
                        [
                            new Node\Arg(
                                new Node\Expr\ArrayDimFetch(
                                    new Node\Expr\Variable('funcCallTemp'),
                                    new Node\Scalar\LNumber(1)
                                ),
                                false,
                                true
                            ),
                        ]
                    );
                    if ($this->disassembler2->isErrorSuppress()) {
                        $expr = new Node\Expr\ErrorSuppress(
                            $expr
                        );
                    }
                    if ($this->getArg(1) !== '') {
                        $expr = new Node\Expr\Assign(
                            $this->getRegisterNode(1),
                            $expr
                        );
                    }
                    return [
                        $arrayPopExpression,
                        new Node\Stmt\Expression($expr),
                    ];
                }

            // Jump ====================
            // if ((*1)) { goto addr((*2) - 1); }
            case 0xac3:
                $if = new Node\Stmt\If_($this->getValueNode(1));
                $valueNode = $this->getRegisterNode(2);
                $if->stmts = [
                    new Node\Stmt\Goto_(Disassembler2::buildLabelName($valueNode->value - 1)),
                ];
                return $if;
            // if ((*1)) { goto addr((*2) - 1); } else { goto addr((*3) - 1); }
            case 0x12ba:
                $if = new Node\Stmt\If_($this->getRegisterNode(1));
                $valueNode = $this->getRegisterNode(2);
                $if->stmts = [
                    new Node\Stmt\Goto_(Disassembler2::buildLabelName('_' . $valueNode->name . '_1')),
                ];
                $valueNode = $this->getRegisterNode(3);
                $if->else = new Node\Stmt\Else_([
                    new Node\Stmt\Goto_(Disassembler2::buildLabelName('_' . $valueNode->name . '_1')),
                ]);
                return $if;
            // if (!(*1)) { goto addr((*2) - 1); }
            case 0x1c37:
                $if = new Node\Stmt\If_(
                    new Node\Expr\BooleanNot(
                        $this->getValueNode(1)
                    )
                );
                $valueNode = $this->getValueNode(2);
                if (!($valueNode instanceof Node\Scalar\LNumber)) {
                    throw new Exception('must goto LNumber');
                }
                $if->stmts = [
                    new Node\Stmt\Goto_(Disassembler2::buildLabelName($valueNode->value - 1)),
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

    public function getArg(int $i)
    {
        return $this->args[$i];
    }

    public function getArgCount()
    {
        return count($this->args);
    }

    public function getRegisterNode(int $i): Node\Expr\Variable
    {
        return Disassembler2::buildRegisterNode($this->getArg($i));
    }

    public function getValueNode(int $i): Node\Expr
    {
        return $this->getValueNodeByKey($this->getArg($i));
    }

    public function getIsXFunctionName(int $i): string
    {
        $v9 = ['undef', 'null', 'false', 'true', 'int', 'double', 'string', 'array', 'object', 'resource', 'reference', 'constant', null, 'bool', 'callable', 'indirect', null, 'ptr', 'void', 'iterable', 'error'];
        return 'is_' . $v9[substr($this->getArg($i), 2)];
    }

    public function getValueNodeByKey(string $key): Node\Expr
    {
        if ($key === '') {
            return new Node\Scalar\String_('');
        } elseif (is_numeric($key)) {
            return Disassembler2::buildRegisterNode($key);
        } else {
            $pos = unpack('c/N*', $key);
            $data = substr($this->disassembler1->data, $pos[1], $pos[2]);
            $type = ord($data[0]);
            switch ($type) {
                case 6:
                case 9:
                    $data1 = substr($data, 1);
                    if ($data1[0] == 0) {
                        return new Node\Scalar\String_(substr($data1, 1));
                    }
                    $password_len = intval($data1[0]);
                    $password = substr($data1, 0, $password_len + 1);
                    $data_encrypted = substr($data1, $password_len + 1);
                    $data_decrypted = openssl_decrypt($data_encrypted, 'AES-128-ECB', $password, OPENSSL_RAW_DATA);
                    if ($type === 6) {
                        return new Node\Scalar\String_($data_decrypted);
                    } else {
                        return PhpParserHelper::parseExpr($data_decrypted);
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

    public function getNextInstruction(): self
    {
        return $this->disassembler1->instructions[$this->next];
    }
}