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
    'name' => 'Group 01',
    'url' => 'http://www.group-01.vn',
);
$id = 9;
$parent = 5;
// $option['position'] = 'right';
$tree->moveNode($id, $parent);

?>
