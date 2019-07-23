<?php

namespace Ganlv\Z5EncryptDecompiler;

class Disassembler1
{
    /** @var string 常量数据 */
    public $data;
    /** @var string 字节码数据 */
    public $text;
    /** @var \Ganlv\Z5EncryptDecompiler\Instruction1[] 分开的指令 */
    public $instructions;
    /** @var int 入口点 */
    public $entryPoint;
    /** @var bool 设置成 false 时，getValue 在 eval 时会抛出异常 */
    public $enableEval = false;

    public function __construct(string $data, string $text)
    {
        $this->data = $data;
        $this->text = $text;
        $this->instructions = [];
    }

    public function disassemble()
    {
        $splitterPos = strpos($this->text, "\1");
        $byteCode = substr($this->text, $splitterPos + 3);
        $this->entryPoint = unpack('n', substr($this->text, $splitterPos + 1, 2))[1];
        $byte_code_len = strlen($byteCode);
        $pointer = 0;
        $i = 0;
        while ($pointer < $byte_code_len) {
            $instruction_len = unpack("N", substr($byteCode, $pointer, 4))[1];
            $pointer += 4;
            $instruction_end = $pointer + $instruction_len;
            $instruction = new Instruction1($i, 0, 0, [], $this);
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

    /**
     * 视情况获取空字符串、寄存器、常量数组、常量整数、浮点数、true、false、null 的代码表达
     *
     * @param string $key
     *
     * @return string
     */
    public function getValueExpr(string $key): string
    {
        if ($key === "") {
            $result = "";
        } elseif (strlen($key < 9) && is_numeric($key)) {
            return '$reg' . $key;
        } else {
            $pos = unpack('c/N*', $key);
            $data = substr($this->data, $pos[1], $pos[2]);
            $type = ord($data[0]);
            switch ($type) {
                case 6:
                case 9:
                    $data1 = substr($data, 1);
                    if ($data1[0] == 0) {
                        $result = substr($data1, 1);
                        break;
                    }
                    $password_len = intval($data1[0]);
                    $password = substr($data1, 0, $password_len + 1);
                    $data_encrypted = substr($data1, $password_len + 1);
                    $data_decrypted = openssl_decrypt($data_encrypted, 'AES-128-ECB', $password, OPENSSL_RAW_DATA);
                    if ($type === 6) {
                        $result = $data_decrypted;
                        break;
                    } else {
                        return $data_decrypted;
                    }
                case 4:
                    $result = (int)substr($data, 1);
                    break;
                case 5:
                    $result = (double)substr($data, 1);
                    break;
                case 7:
                    $result = true;
                    break;
                case 8:
                    $result = false;
                    break;
                default:
                    $result = null;
            }
        }
        return var_export($result, true);
    }
}