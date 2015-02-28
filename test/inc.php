<?php
header('content-type: text/plain');

function pre($input, $e = false) {
    if ($input === null) $input = 'NULL';
    printf("%s\n", preg_replace('[(\w+):.*?\:private]', '\\1:private', print_r($input, 1))); $e && exit;
}
function prd($input, $e = false) {
    var_dump($input); print("\n"); $e && exit;
}
