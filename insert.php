<?php

require_once 'nested.set.php';
$arrConnect = array(
    'server' => 'localhost',
    'username' => 'root',
    'password' => '',
    'db' => 'nested_set',
    'table' => 'menu',
);
$tree = new Nest_Set($arrConnect);
$data = array(
    'name' => 'Group B',
    'url' => 'http://www.group-b.vn',
);
$parent = 1;
$option['position'] = 'after';
$option['brother_id'] = 7;
$tree->insertNode($data, $parent, $option);

?>
