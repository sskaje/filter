<?php
require(__DIR__ . '/filter.php');

$filter = new filter('127.0.0.1', 29090);

if ($argc < 2) {
    echo "php {$argv[0]} WORD\n";
    exit;
}

$filter->match($argv[1], $p);
var_dump($p);