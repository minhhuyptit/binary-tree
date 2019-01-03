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
    'name' => 'Group O.1',
    'url' => 'http://www.group-o1.vn',
);
$parent = 1;
$option['position'] = 'after';
$option['brother_id'] = 9;
$tree->insertNode($data, $parent, $option);

?>
