<?php
ini_set('error_reporting', -1);
ini_set('display_errors', 'on');

header('content-type: text/plain; chartset=utf-8');

function pre($input, $e = false) {
    if ($input === null) $input = 'NULL';
    elseif ($input === true) $input = 'TRUE';
    elseif ($input === false) $input = 'FALSE';
    printf("%s\n", preg_replace('[(\w+):.*?\:private]', '\\1:private', print_r($input, 1))); $e && exit;
}
function prd($input, $e = false) {
    var_dump($input); print("\n"); $e && exit;
}

$autoload = (require(__dir__.'/../../src/Autoload.php'))();
$autoload->register();
