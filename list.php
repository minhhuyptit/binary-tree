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
$sql = "SELECT * FROM {$tree->getTable()}
        WHERE lft != 0
        ORDER BY lft ASC";
$result = mysql_query($sql, $tree->getConnect());
while ($row = mysql_fetch_assoc($result)) {{
    $strMenu = '';
    if ($row['level'] == 1) {
        $strMenu = '+ ' . $row['name'];
    } else {
        $strMenu = str_repeat('|&mdash;', $row['level']-1) . $row['name'];
    }

    echo '<br>' . $strMenu;
}

}
?>