<?php

require(__DIR__ . '/filter.php');

$filter = new filter('127.0.0.1', 29090);

$p = array();
$r = $filter->match("abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234", $p);
dump_result($r, $p);

$p = array();
$r = $filter->match("我说，好吧，就这样吧", $p);
dump_result($r, $p);

$p = array();
$r = $filter->match("I feel like it is just a 测试", $p);
dump_result($r, $p);

$p = array();
$r = $filter->match("就这样啊，爱爱爱", $p);
dump_result($r, $p);

$p = array();
$r = $filter->match(str_repeat("就这样啊，爱", 1000), $p);
dump_result($r, $p);

$p = array();
$r = $filter->match("I feel like it is just a 测试", $p);
dump_result($r, $p);


$p = array();
$r = $filter->filter("I feel like it is just a 测试", '*', false);
var_dump($r);

$p = array();
$r = $filter->filter("I feel like it is just a 测试", '*', true);
var_dump($r);

function dump_result($count, $result_pairs)
{
    echo "\nCount=$count\n";
    foreach ($result_pairs as $row) {
        echo "Pos={$row['pos']}, Len={$row['len']}, Flag={$row['flag']}\n";
    }
}
