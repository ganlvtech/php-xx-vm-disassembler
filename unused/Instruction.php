<?php

namespace Ganlv\Z5EncryptDecompiler;

class Instruction
{
    public $name = '';
    public $index = 0;
    public $type = 0;
    public $next = 0;
    public $args = [];
    public $nextInstruction = null;
    /** @var \Ganlv\Z5EncryptDecompiler\Disassembler */
    public $disassembler = null;

    public function __toString()
    {
        return sprintf('%04d - %s (0x%x) %s', $this->index, $this->name, $this->type, '');//, $this->info());
    }

    public function code()
    {
        switch ($this->type) {
            case 0x1a67:
                return $this->getRegister(1) . ' = __SHAREVM_FUNCTION__;';
            case 0x1d89:
                return 'return ' . $this->getValue(1) . ';';
            case 0x930:
                return $this->pushFuncArg($this->getValue(1));
            case 0x1384:
                return $this->getRegister(1) . ' = __CLASS__;';
            case 0xb2a:
                if (count($this->args) > 4) {
                    return $this->getRegister(1) . ' = ' . $this->getRegister(2) . ' = ' . $this->getValue(3) . ';';
                } else {
                    return $this->getRegister(1) . ' = ' . $this->getValue(2) . ';';
                }
            case 0xfe6:
                return $this->pushFuncArg($this->getValue(1));
            case 0xac3:
                return 'if (' . $this->getValue(1) . ') { goto addr' . ($this->getValue(2) - 1) . '; }';
            case 0x189e:
                return $this->getRegister(1) . ' = count(' . $this->getRegister(2) . ');';
            case 0x14b9:
                return $this->getRegister(1) . ' = ' . $this->getIsX(4) . '(' . $this->getValue(2) . ');';
            case 0x1783:
                return $this->getRegister(1) . ' = ' . $this->getValue(2) . ' + ' . $this->getValue(3) . ';';
            case 0x1b2e:
                if ($this->getArg(3) !== '') {
                    $type = $this->getArg(3);
                    $value = $this->getValue(2);
                } else {
                    $type = $this->getArg(2);
                    $value = $this->getValue(1);
                }
                if ($type === 'e') {
                    $type = 'eval(' . $value . ');';
                } else {
                    if ($type === 'i') {
                        $type = '(include_once ' . $value . ');';
                    } else {
                        $type = '(include ' . $value . ');';
                    }
                }
                if ($this->getArg(3) !== '') {
                    return $this->getRegister(1) . ' = ' . $type;
                }
                return $type;
            case 0x602:
                $type = $this->getValue(2);
                if ($type === '') {
                    $type = __CLASS__;
                }
                return $this->buildNewCall($type, $this->getArg(1));
            case 0x6f9:
                return $this->getRegister(1) . ' = ' . $this->getValue(2) . ' < ' . $this->getValue(3) . ';';
            case 0x11af:
                return $this->buildFuncCall($this->getValue(1));
            case 0x1ec1:
                return $this->getRegister(1) . ' = __FUNCTION__;';
            case 0x514:
                return $this->getRegister(1) . ' = ' . $this->getValue(1) . ' - ' . $this->getValue(2) . ';';
            case 0x72c:
                return $this->setSuppressError(false);
            case 0x561:
                return ';';
            case 0x16a8:
                return 'echo ' . $this->getValue(1) . ';';
            case 0x614:
                return ';';
            case 0x1a27:
                if ($this->getArg(1) === "") {
                    $v33 = $this;
                } else {
                    $v33 = $this->getValue(1);
                }
                return $this->buildMethodCall($v33, $this->getValue(2));
            case 0x790:
                return 'exit(' . $this->getValue(1) . ');';
            case 0xedc:
                return ';';
            case 0x1ca8:
                return $this->pushFuncArg($this->getValue(1));
            case 0x784:
                return $this->buildFuncCall($this->getValue(2));
            case 0x16fd:
                return 'unset(' . $this->getRegister(1) . ');';
            case 0xb6f:
                return $this->getRegister(1) . ' = ' . $this->getValue(2) . ' * ' . $this->getValue(3) . ';';
            case 0xce1:
                $funcCall = $this->popFuncCall();
                if ($this->isSuppressError()) {
                    $code = '@' . self::buildFunctionName($funcCall[0]) . '(' . implode(', ', $funcCall[1]) . ');';
                } else {
                    $code = self::buildFunctionName($funcCall[0]) . '(' . implode(', ', $funcCall[1]) . ');';
                }
                if ($this->getArg(1) !== '') {
                    return $this->getRegister(1) . ' = ' . $code;
                }
                return $code;
            case 0x12ba:
                return 'if (' . $this->getRegister(1) . ') { goto addr' . ($this->getValue(2) - 1) . '; } else { goto addr' . ($this->getValue(3) - 1) . '; }';
            case 0xa33:
                return $this->getRegister(1) . ' = ' . $this->getValue(2) . ' == ' . $this->getValue(3) . ';';
            case 0x1250:
                return $this->getRegister(1) . ' = ' . $this->getValue(2) . ' - ' . $this->getValue(3) . ';';
            case 0x1c37:
                return 'if (!(' . $this->getValue(1) . ')) { goto addr' . ($this->getValue(2) - 1) . '; }';
            case 0x13c5:
                return $this->getRegister(1) . ' = ' . $this->getValue(2) . ' === ' . $this->getValue(3) . ';';
            case 0x12c2:
                return $this->getRegister(1) . ' = ' . $this->getValue(2) . ' !== ' . $this->getValue(3) . ';';
            case 0x1110:
                return $this->getRegister(1) . ' =& $' . $this->getValue(2) . ';';
            case 0xfea:
                return $this->getRegister(1) . ' = (bool)' . $this->getValue(2) . ';';
            case 0x936:
                return $this->setSuppressError(true);
            case 0x157d:
                $funcCall = $this->popFuncCall();
                if (isset($funcCall[2])) {
                    if ($funcCall[2][0] === 'new') {
                        if ($this->isSuppressError()) {
                            $code = '@new ' . self::buildFunctionName($funcCall[0])  . '(' . implode(', ', $funcCall[1]) . ');';
                        } else {
                            $code = 'new ' . self::buildFunctionName($funcCall[0])  . '(' . implode(', ', $funcCall[1]) . ');';
                        }
                        return '$reg' . $funcCall[2][1] . ' = ' . $code;
                    }
                } else {
                    if ($this->isSuppressError()) {
                        $code = '@' . self::buildFunctionName($funcCall[0])  . '(' . implode(', ', $funcCall[1]) . ');';
                    } else {
                        $code = self::buildFunctionName($funcCall[0])  . '(' . implode(', ', $funcCall[1]) . ');';
                    }
                    if ($this->getArg(1) !== '') {
                        return $this->getRegister(1) . ' = ' . $code;
                    }
                    return $code;
                }
                return 'cannot reach';
            case 0x1207:
                return $this->getRegister(1) . ' = !(bool)' . $this->getValue(2);
        }
    }

    public function getRegister($i)
    {
        return '$reg' . $this->getArg($i);
    }

    public function getArg($i)
    {
        return $this->args[$i];
    }

    public function getArgCount() {
        return count($this->args);
    }

    public function getValue($i)
    {
        return $this->disassembler->getValue($this->getArg($i));
    }

    public function pushFuncArg($value)
    {
        return $this->disassembler->pushFuncArg($value);
    }

    public function getIsX($i)
    {
        return $this->disassembler->getIsX($this->getArg($i));
    }

    public function buildNewCall($func_name, $newRegister)
    {
        return $this->disassembler->buildNewCall($func_name, $newRegister);
    }

    public function buildFuncCall($func_name)
    {
        return $this->disassembler->buildFuncCall($func_name);
    }

    public function setSuppressError($value)
    {
        return $this->disassembler->setSuppressError($value);
    }

    public function buildMethodCall($class, $func_name)
    {
        return $this->disassembler->buildMethodCall($class, $func_name);
    }

    public function popFuncCall()
    {
        return $this->disassembler->popFuncCall();
    }

    public function isSuppressError()
    {
        return $this->disassembler->isSuppressError;
    }

    public static function buildFunctionName($name)
    {
        if (is_array($name)) {
            return $name[0] . '->' . $name[1];
        }
        return $name;
    }
}