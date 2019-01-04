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
    'name' => 'Group O.1.2',
    'url' => 'http://www.group-o12.vn',
);
$id = 11;
$newParentID = 4;
$tree->updateNode($data, $id, $newParentID);
// $parent = 1;
// $option['position'] = 'after';
// $option['brother_id'] = 9;
// $tree->insertNode($data, $parent, $option);

?>