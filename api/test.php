<?php

require(__DIR__ . '/filter.php');

$filter = new filter('127.0.0.1', 29090);

$p = array();
$r = $filter->test("abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234abdcd1234", $p);
dump_result($r, $p);

$p = array();
$r = $filter->test("我说，好吧，就这样吧", $p);
dump_result($r, $p);

$p = array();
$r = $filter->test("I feel like it is just a 测试", $p);
dump_result($r, $p);

$p = array();
$r = $filter->test("就这样啊，爱爱爱", $p);
dump_result($r, $p);

$p = array();
$r = $filter->test(str_repeat("就这样啊，爱", 1000), $p);
dump_result($r, $p);

$p = array();
$r = $filter->test("I feel like it is just a 测试", $p);
dump_result($r, $p);


function dump_result($count, $result_pairs)
{
    echo "\nCount=$count\n";
    foreach ($result_pairs as $row) {
        echo "Pos={$row['pos']}, Len={$row['len']}\n";
    }
}
