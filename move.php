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

//Move Right
// $id = 9;
// $parent = 5;
// $tree->moveNode($id, $parent);

//Move Left
// $id = 9;
// $parent = 5;
// $options['position'] = 'left';
// $tree->moveNode($id, $parent, $options);

//Move Before
// $id = 9;
// $parent = 1;
// $options['position'] = 'before';
// $options['brother_id'] = 6;
// $tree->moveNode($id, $parent, $options);

//Move After
// $id = 9;
// $parent = 1;
// $options['position'] = 'after';
// $options['brother_id'] = 4;
// $tree->moveNode($id, $parent, $options);

// $id = 9;
// $tree->moveUp($id);

$id = 9;
$tree->moveDown($id);

?>
