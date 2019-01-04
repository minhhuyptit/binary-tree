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
    'name' => 'Group B2',
    'url' => 'http://www.group-b2.vn',
);
$parent = 15;
// $option['position'] = 'after';
// $option['brother_id'] = 9;
$tree->insertNode($data, $parent);

?>