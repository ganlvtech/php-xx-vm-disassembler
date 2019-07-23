<?php

namespace Ganlv\Z5EncryptDecompiler;

class Disassembler
{
    public $data;
    public $text;
    public $file;
    public $pointer = null;
    public $instructions = [];
    public $funcStack = [];
    public $funcStackLen = 0;
    public $isSuppressError = false;

    public function loadInstructions()
    {
        $splitterPos = strpos($this->text, "\1");
        $byteCode = substr($this->text, $splitterPos + 3);
        $this->pointer = unpack('n', substr($this->text, $splitterPos + 1, 2))[1];
        $byte_code_len = strlen($byteCode);
        $pointer = 0;
        $i = 0;
        while ($pointer < $byte_code_len) {
            $instruction_len = unpack("N", substr($byteCode, $pointer, 4))[1];
            $pointer += 4;
            $instruction_end = $pointer + $instruction_len;
            $instruction = new Instruction();
            $instruction->index = $i;
            $instruction->disassembler = $this;
            $instruction->next = unpack('n', substr($byteCode, $pointer, 2))[1];
            $pointer += 2;
            $instruction->type = unpack('N', substr($byteCode, $pointer, 4))[1];
            $pointer += 4;
            $args = [''];
            while ($pointer < $instruction_end) {
                $arg_len = unpack("N", substr($byteCode, $pointer, 4))[1];
                $pointer += 4;
                $args[] = $arg_len > 0 ? substr($byteCode, $pointer, $arg_len) : '';
                $pointer += $arg_len;
            }
            $instruction->args = $args;
            $this->instructions[] = $instruction;
            $i++;
        }
    }

    public function disassemble()
    {
        $visited = [];
        $pointer = $this->pointer;
        while (!in_array($pointer, $visited)) {
            $visited[] = $pointer;
            /** @var \Ganlv\Z5EncryptDecompiler\Instruction $instruction */
            $instruction = $this->instructions[$pointer];
            echo 'addr' . $instruction->index . ':', "\t", $instruction->code(), PHP_EOL;
            if ($instruction->next < count($this->instructions)) {
                $instruction->nextInstruction = $this->instructions[$instruction->next];
            } else {
                break;
            }
            $pointer = $instruction->next;
        }
    }

    public function chain()
    {
        $visited = [];
        $i = $this->pointer;

        while (!in_array($i, $visited)) {
            $visited[] = $i;
            /** @var \Ganlv\Z5EncryptDecompiler\Instruction $instruction */
            $instruction = $this->instructions[$i];
            echo 'addr' . $instruction->index . ':', "\t", $instruction->code(), PHP_EOL;
            if ($instruction->next < count($this->instructions)) {
                $instruction->nextInstruction = $this->instructions[$instruction->next];
            } else {
                break;
            }
            $i = $instruction->next;
        }
    }

    public function getValue($key)
    {
        if ($key === "") {
            return "''";
        } elseif (strlen($key < 9) && is_numeric($key)) {
            return '$reg' . $key;
        } else {
            $pos = unpack('c/N*', $key);
            $data = substr($this->data, $pos[1], $pos[2]);
            $type = ord($data[0]);
            switch ($type) {
                case 6:
                case 9:
                    $v23 = substr($data, 1);
                    if ($v23[0] == 0) {
                        return substr($v23, 1);
                    }
                    $v25 = intval($v23[0]);
                    $v26 = substr($v23, 0, $v25 + 1);
                    $v27 = substr($v23, $v25 + 1);
                    $v28 = openssl_decrypt($v27, 'AES-128-ECB', $v26, OPENSSL_RAW_DATA);
                    if ($type === 6) {
                        return var_export($v28, true);
                    } else {
                        return $v28;
                    }
                    break;
                case 4:
                    return (int)substr($data, 1);
                case 5:
                    return (double)substr($data, 1);
                case 7:
                    return true;
                case 8:
                    return false;
                default:
                    return null;
            }
        }
    }

    public function getIsX($key)
    {
        $v9 = ['undef', 'null', 'false', 'true', 'int', 'double', 'string', 'array', 'object', 'resource', 'reference', 'constant', null, 'bool', 'callable', 'indirect', null, 'ptr', 'void', 'iterable', 'error'];
        return 'is_' . $v9[substr($key, 2)];
    }

    public function buildFuncCall($func_name)
    {
        $this->funcStack[] = [$func_name, []];
        $this->funcStackLen++;
        return '// build_func_call(' . $func_name . ')';
    }

    public function buildMethodCall($class, $func_name)
    {
        $this->funcStack[] = [[$class, $func_name], []];
        $this->funcStackLen++;
        return '// build_method_call(' . $class . ', ' . $func_name . ')';
    }

    public function buildNewCall($func_name, $newRegister)
    {
        $this->funcStack[] = [$func_name, [], ['new', $newRegister]];
        $this->funcStackLen++;
        return '// build_new_call(' . $func_name . ', ' . $newRegister . ')';
    }

    public function pushFuncArg($value)
    {
        $this->funcStack[$this->funcStackLen - 1][1][] = $value;
        return '// push_func_arg(' . $value . ')';
    }

    public function popFuncCall()
    {
        $this->funcStackLen--;
        return array_pop($this->funcStack);
    }

    public function setSuppressError($value)
    {
        $this->isSuppressError = $value;
        if ($value) {
            return '// Suppress Error On';
        } else {
            return '// Suppress Error Off';
        }
    }
}