<?php

use PhpParser\Error;
use PhpParser\ParserFactory;

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

$prettyPrinter = new PhpParser\PrettyPrinter\Standard;
$newCode = $prettyPrinter->prettyPrintFile($ast);

file_put_contents('tests/samples/t2_formatted.php_', $newCode);
