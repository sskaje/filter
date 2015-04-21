<?php

require(__DIR__ . '/filter.php');

$filter = new filter('127.0.0.1', 29090);

echo "\n=======match()=======\n";
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
$r = $filter->match("这个方案的专业化程度很高啊", $p);
dump_result($r, $p);

# 临界值测试
$p = array();
$r = $filter->match(str_repeat('az', 511) . 'abcd', $p);
dump_result($r, $p);

echo "\n=======filter()=======\n";
$p = array();
$r = $filter->filter("I feel like it is just a 测试", '*', false);
var_dump($r);

$p = array();
$r = $filter->filter("I feel like it is just a 测试", '*', true);
var_dump($r);

echo "\n=======ping()=======\n";
$r = $filter->ping();
var_dump($r);

$r = $filter->ping("");
var_dump($r);

$r = $filter->ping("123123123");
var_dump($r);

function dump_result($count, $result_pairs)
{
    echo "\nCount=$count\n";
    foreach ($result_pairs as $row) {
        echo "Pos={$row['pos']}, Len={$row['len']}, Flag={$row['flag']}\n";
    }
}
