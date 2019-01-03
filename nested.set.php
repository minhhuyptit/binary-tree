<?php
class Nest_Set {

    protected $_connect;
    protected $_db;
    protected $_table;
    public $_data;
    public $_parent_id;
    public $_id;

    public function __construct($param = array(), $adapter = 'mysql') {
        if (!empty($param)) {
            if ($adapter == 'mysql') {
                $link = mysql_connect($param['server'], $param['username'], $param['password']);
                if (!$link) {
                    die('Could not connect: ' . mysql_error());
                } else {
                    $this->_connect = $link;
                    $this->_db = $param['db'];
                    $this->_table = $param['table'];
                    $this->setDb();
                }
            }
        }
    }

    public function setConnect($connect) {
        $this->_connect = $connect;
    }

    public function setDb($db = null) {
        if ($db != null) {
            $this->_db = $db;
        }
        mysql_select_db($this->_db, $this->_connect);
    }

    public function setTable($table) {
        $this->_table = $table;
    }

    public function insertNode($data, $parent = 1, $options = null) {
        $this->_data = $data;
        $this->_parent_id = $parent;

        if ($options['position'] == 'right' || $options['position'] == null) {
            $this->insertRight();
        }
        if ($options['position'] == 'left') {
            $this->insertLeft();
        }
        if ($options['position'] == 'before') {
            $this->insertBefore($options['brother_id']);
        }
        if ($options['position'] == 'after') {
            $this->insertAfter($options['brother_id']);
        }
    }

    protected function insertLeft() {
        $parentInfo = $this->getNodeInfo($this->_parent_id);
        $parentLeft = $parentInfo['lft'];

        $sqlUpdateLeft = "UPDATE {$this->_table} SET lft = (lft+2) WHERE lft >= " . ($parentLeft + 1);
        mysql_query($sqlUpdateLeft, $this->_connect);

        $sqlUpdateRight = "UPDATE {$this->_table} SET rgt = (rgt+2) WHERE rgt >= " . ($parentLeft + 1);
        mysql_query($sqlUpdateRight, $this->_connect);

        $data = $this->_data;
        $data['parent'] = $parentInfo['id'];
        $data['lft'] = $parentLeft + 1;
        $data['rgt'] = $parentLeft + 2;
        $data['level'] = $parentInfo['level'] + 1;

        $newQuery = $this->createInsertQuery($data);
        $sqlInsert = "INSERT INTO $this->_table ({$newQuery['cols']}) VALUES ({$newQuery['vals']})";
        mysql_query($sqlInsert, $this->_connect);

    }

    protected function insertRight() {
        $parentInfo = $this->getNodeInfo($this->_parent_id);
        $parentRight = $parentInfo['rgt'];

        $sqlUpdateLeft = "UPDATE {$this->_table} SET lft = (lft+2) WHERE lft > {$parentRight}";
        mysql_query($sqlUpdateLeft, $this->_connect);

        $sqlUpdateRight = "UPDATE {$this->_table} SET rgt = (rgt+2) WHERE rgt >= {$parentRight}";
        mysql_query($sqlUpdateRight, $this->_connect);

        $data = $this->_data;
        $data['parent'] = $parentInfo['id'];
        $data['lft'] = $parentRight;
        $data['rgt'] = $parentRight + 1;
        $data['level'] = $parentInfo['level'] + 1;

        $newQuery = $this->createInsertQuery($data);
        $sqlInsert = "INSERT INTO $this->_table ({$newQuery['cols']}) VALUES ({$newQuery['vals']})";
        mysql_query($sqlInsert, $this->_connect);
    }

    protected function insertBefore($brother_id) {
        $parentInfo     = $this->getNodeInfo($this->_parent_id);
        $brotherInfo    = $this->getNodeInfo($brother_id);

        $sqlUpdateLeft = "UPDATE {$this->_table} SET lft = (lft+2) WHERE lft >= " . $brotherInfo['lft'];
        mysql_query($sqlUpdateLeft, $this->_connect);

        $sqlUpdateRight = "UPDATE {$this->_table} SET rgt = (rgt+2) WHERE rgt >= " . ($brotherInfo['lft'] + 1);
        mysql_query($sqlUpdateRight, $this->_connect);

        $data = $this->_data;
        $data['parent'] = $parentInfo['id'];
        $data['lft'] = $brotherInfo['lft'];
        $data['rgt'] = $brotherInfo['lft'] + 1;
        $data['level'] = $parentInfo['level'] + 1;

        $newQuery = $this->createInsertQuery($data);
        $sqlInsert = "INSERT INTO $this->_table ({$newQuery['cols']}) VALUES ({$newQuery['vals']})";
        mysql_query($sqlInsert, $this->_connect);
    }

    protected function insertAfter($brother_id) {
        $parentInfo     = $this->getNodeInfo($this->_parent_id);
        $brotherInfo    = $this->getNodeInfo($brother_id);

        $sqlUpdateLeft = "UPDATE {$this->_table} SET lft = (lft+2) WHERE lft > " . $brotherInfo['rgt'];
        mysql_query($sqlUpdateLeft, $this->_connect);

        $sqlUpdateRight = "UPDATE {$this->_table} SET rgt = (rgt+2) WHERE rgt > " . ($brotherInfo['rgt']);
        mysql_query($sqlUpdateRight, $this->_connect);

        $data = $this->_data;
        $data['parent'] = $parentInfo['id'];
        $data['lft'] = $brotherInfo['rgt'] + 1;
        $data['rgt'] = $brotherInfo['rgt'] + 2;
        $data['level'] = $parentInfo['level'] + 1;

        $newQuery = $this->createInsertQuery($data);
        $sqlInsert = "INSERT INTO $this->_table ({$newQuery['cols']}) VALUES ({$newQuery['vals']})";
        mysql_query($sqlInsert, $this->_connect);
    }

    public function moveNode($id, $parent, $options = null){
        $this->_id = $id;
        $this->_parent_id = $parent;
        if ($options == null || $options['position'] == 'right') {
            $this->moveRight();
        }
        if ($options['position'] == 'left') {
            $this->movetLeft();
        }
        if ($options['position'] == 'before') {
            $this->moveBefore($options['brother_id']);
        }
        if ($options['position'] == 'after') {
            $this->moveAfter($options['brother_id']);
        }
    }

    protected function moveLeft(){
        
    }

    protected function moveRight(){
        $infoMoveNode = $this->getNodeInfo($this->_id);
        $lftMoveNode = $infoMoveNode['lft'];
        $rgtMoveNode = $infoMoveNode['rgt'];


        //1. Tách nhánh khỏi cây
        $sqlSelect = "UPDATE $this->_table 
                        SET lft = (lft - $lftMoveNode), 
                            rgt = (rgt - $rgtMoveNode)
                        WHERE lft BETWEEN $lftMoveNode AND $rgtMoveNode";
        // mysql_query($sqlInsert, $this->_connect);
        echo '<br>' . $sqlSelect;

        //2. Tính độ dài của nhánh chúng ta cắt
        $lengthModeNode = $this->lengthNode($lftMoveNode, $rgtMoveNode);


        //3. Cập nhật giá trị các node nằm bên phải của node tách
        $sqlUpdateLeft = "UPDATE $this->_table 
                            SET lft = (lft - $lengthModeNode) 
                            WHERE lft > $rgtMoveNode";
        // mysql_query($sqlInsert, $this->_connect);
        echo '<br>' . $sqlUpdateLeft;

        $sqlUpdateRight = "UPDATE $this->_table 
                            SET rgt = (rgt - $lengthModeNode) 
                            WHERE rgt > $rgtMoveNode";
        // mysql_query($sqlInsert, $this->_connect);
        echo '<br>' . $sqlUpdateRight;
        
        // echo "<pre>";
        // print_r($infoMoveNode);
        // echo "</pre>";
    }

    public function lengthNode($lftMoveNode, $rgtMoveNode){
        $lengthMoveNode = $rgtMoveNode - $lftMoveNode + 1;
        return $lengthMoveNode;
    }

    protected function moveBefore($brother_id){
        
    }

    protected function moveAfter($brother_id){
        
    }

    protected function createInsertQuery($data = null) {
        if ($data != null && !empty($data)) {
            $cols = '';
            $vals = '';
            foreach ($data as $key => $value) {
                $cols .= "`$key`,";
                $vals .= "'$value',";
            }
            $newQuery['cols'] = rtrim($cols, ',');
            $newQuery['vals'] = rtrim($vals, ',');
            return $newQuery;
        }
    }

    public function getNodeInfo($id) {
        $sql = "SELECT *FROM {$this->_table} WHERE id = {$id}";
        $result = mysql_query($sql, $this->_connect);
        $row = mysql_fetch_assoc($result);
        return $row;
    }

}
?>
