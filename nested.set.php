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
        $parentInfo = $this->getNodeInfo($this->_parent_id);
        $brotherInfo = $this->getNodeInfo($brother_id);

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
        $parentInfo = $this->getNodeInfo($this->_parent_id);
        $brotherInfo = $this->getNodeInfo($brother_id);

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

    public function moveNode($id, $parent, $options = null) {
        $this->_id = $id;
        $this->_parent_id = $parent;
        if ($options == null || $options['position'] == 'right') {
            $this->moveRight();
        }
        if ($options['position'] == 'left') {
            $this->moveLeft();
        }
        if ($options['position'] == 'before') {
            $this->moveBefore($options['brother_id']);
        }
        if ($options['position'] == 'after') {
            $this->moveAfter($options['brother_id']);
        }
    }

    protected function moveLeft() {
        $infoMoveNode = $this->getNodeInfo($this->_id);
        $lftMoveNode = $infoMoveNode['lft'];
        $rgtMoveNode = $infoMoveNode['rgt'];

        //1. Tách nhánh khỏi cây
        $sqlSelect = "UPDATE $this->_table
         SET lft = (lft - $lftMoveNode),
             rgt = (rgt - $rgtMoveNode)
         WHERE lft BETWEEN $lftMoveNode AND $rgtMoveNode";
        mysql_query($sqlSelect, $this->_connect);
        // echo '<br>' . $sqlSelect;

        //2. Tính độ dài của nhánh chúng ta cắt
        $lengthMoveNode = $this->lengthNode($lftMoveNode, $rgtMoveNode);

        //3. Cập nhật giá trị các node nằm bên phải của node tách
        $sqlUpdateLeft = "UPDATE $this->_table
                            SET lft = (lft - $lengthMoveNode)
                            WHERE lft > $rgtMoveNode";
        mysql_query($sqlUpdateLeft, $this->_connect);
        // echo '<br>'.$sqlUpdateLeft;

        $sqlUpdateRight = "UPDATE $this->_table
                            SET rgt = (rgt - $lengthMoveNode)
                            WHERE rgt > $rgtMoveNode";
        mysql_query($sqlUpdateRight, $this->_connect);
        // echo '<br>'.$sqlUpdateRight;

        //4. Lấy thông tin của node cha
        $infoParentNode = $this->getNodeInfo($this->_parent_id);
        $leftParentNode = $infoParentNode['lft'];

        //5. Cập nhật các giá trị trước khi gắn nhánh vào
        $sqlUpdateLeft = "UPDATE $this->_table
                            SET lft = (lft + $lengthMoveNode)
                            WHERE lft > $leftParentNode";
        mysql_query($sqlUpdateLeft, $this->_connect);
        // echo '<br>'.$sqlUpdateLeft;

        $sqlUpdateRight = "UPDATE $this->_table
                            SET rgt = (rgt + $lengthMoveNode)
                            WHERE rgt > $leftParentNode";
        mysql_query($sqlUpdateRight, $this->_connect);
        // echo '<br>'.$sqlUpdateRight;

        //6. Cập nhật level cho nhánh sắp được gán vào cây
        $levelMoveNode = $infoMoveNode['level'];
        $levelParentNode = $infoParentNode['level'];
        $sqlUpdateLevel = "UPDATE $this->_table
                            SET level = (level - $levelMoveNode + $levelParentNode + 1)
                            WHERE rgt <= 0";
        mysql_query($sqlUpdateLevel, $this->_connect);
        // echo '<br>'.$sqlUpdateLevel;

        //7. Cập nhật nhánh trước khi gán vào node mới
        $sqlUpdateLeft = "UPDATE $this->_table
                            SET lft = (lft + {$infoParentNode['lft']} + 1)
                            WHERE rgt <= 0";
        mysql_query($sqlUpdateLeft, $this->_connect);
        // echo '<br>'.$sqlUpdateLeft;

        $sqlUpdateRight = "UPDATE $this->_table
                            SET rgt = (rgt + {$infoParentNode['lft']} + {$lengthMoveNode})
                            WHERE rgt <= 0";
        mysql_query($sqlUpdateRight, $this->_connect);
        // echo '<br>'.$sqlUpdateRight;

        //8. Gán vào node cha
        $sqlUpdateNode = "UPDATE $this->_table
                            SET parent = {$infoParentNode['id']}
                            WHERE id = {$infoMoveNode['id']}";
        mysql_query($sqlUpdateNode, $this->_connect);
        // echo '<br>'.$sqlUpdateNode;

    }

    protected function moveRight() {
        $infoMoveNode = $this->getNodeInfo($this->_id);
        $lftMoveNode = $infoMoveNode['lft'];
        $rgtMoveNode = $infoMoveNode['rgt'];

        //1. Tách nhánh khỏi cây
        $sqlSelect = "UPDATE $this->_table
                        SET lft = (lft - $lftMoveNode),
                            rgt = (rgt - $rgtMoveNode)
                        WHERE lft BETWEEN $lftMoveNode AND $rgtMoveNode";
        mysql_query($sqlSelect, $this->_connect);
        // echo '<br>'.$sqlSelect;
        //2. Tính độ dài của nhánh chúng ta cắt
        $lengthMoveNode = $this->lengthNode($lftMoveNode, $rgtMoveNode);

        //3. Cập nhật giá trị các node nằm bên phải của node tách
        $sqlUpdateLeft = "UPDATE $this->_table
                            SET lft = (lft - $lengthMoveNode)
                            WHERE lft > $rgtMoveNode";
        mysql_query($sqlUpdateLeft, $this->_connect);
        // echo '<br>'.$sqlUpdateLeft;

        $sqlUpdateRight = "UPDATE $this->_table
                            SET rgt = (rgt - $lengthMoveNode)
                            WHERE rgt > $rgtMoveNode";
        mysql_query($sqlUpdateRight, $this->_connect);
        // echo '<br>'.$sqlUpdateRight;

        //4. Lấy thông tin của node cha
        $infoParentNode = $this->getNodeInfo($this->_parent_id);
        $rightParentNode = $infoParentNode['rgt'];

        //5. Cập nhật các giá trị trước khi gắn nhánh vào
        $sqlUpdateLeft = "UPDATE $this->_table
                            SET lft = (lft + $lengthMoveNode)
                            WHERE lft > $rightParentNode";
        mysql_query($sqlUpdateLeft, $this->_connect);
        // echo '<br>'.$sqlUpdateRight;

        $sqlUpdateRight = "UPDATE $this->_table
                            SET rgt = (rgt + $lengthMoveNode)
                            WHERE rgt >= $rightParentNode";
        mysql_query($sqlUpdateRight, $this->_connect);
        // echo '<br>'.$sqlUpdateRight;

        //6. Cập nhật level cho nhánh sắp được gán vào cây
        $levelMoveNode = $infoMoveNode['level'];
        $levelParentNode = $infoParentNode['level'];
        $sqlUpdateLevel = "UPDATE $this->_table
                            SET level = (level - $levelMoveNode + $levelParentNode + 1)
                            WHERE rgt <= 0";
        mysql_query($sqlUpdateLevel, $this->_connect);
        // echo '<br>'.$sqlUpdateRight;

        //7. Cập nhật nhánh trước khi gán vào node mới
        $sqlUpdateLeft = "UPDATE $this->_table
                            SET lft = (lft + {$infoParentNode['rgt']})
                            WHERE rgt <= 0";
        mysql_query($sqlUpdateLeft, $this->_connect);
        // echo '<br>'.$sqlUpdateRight;

        $sqlUpdateRight = "UPDATE $this->_table
                            SET rgt = (rgt + {$infoParentNode['rgt']} + {$lengthMoveNode} - 1)
                            WHERE rgt <= 0";
        mysql_query($sqlUpdateRight, $this->_connect);
        // echo '<br>'.$sqlUpdateRight;

        //8. Gán vào node cha
        $sqlUpdateNode = "UPDATE $this->_table
                            SET parent = {$infoParentNode['id']}
                            WHERE id = {$infoMoveNode['id']}";
        mysql_query($sqlUpdateNode, $this->_connect);
        // echo '<br>'.$sqlUpdateNode;

        // echo "<pre>";
        // print_r($infoParentNode);
        // echo "</pre>";
    }

    protected function moveBefore($brother_id) {
        $infoMoveNode = $this->getNodeInfo($this->_id);
        $lftMoveNode = $infoMoveNode['lft'];
        $rgtMoveNode = $infoMoveNode['rgt'];

        //1. Tách nhánh khỏi cây
        $sqlSelect = "UPDATE $this->_table
         SET lft = (lft - $lftMoveNode),
             rgt = (rgt - $rgtMoveNode)
         WHERE lft BETWEEN $lftMoveNode AND $rgtMoveNode";
        mysql_query($sqlSelect, $this->_connect);
        // echo '<br>' . $sqlSelect;

        //2. Tính độ dài của nhánh chúng ta cắt
        $lengthMoveNode = $this->lengthNode($lftMoveNode, $rgtMoveNode);

        //3. Cập nhật giá trị các node nằm bên phải của node tách
        $sqlUpdateLeft = "UPDATE $this->_table
                            SET lft = (lft - $lengthMoveNode)
                            WHERE lft > $rgtMoveNode";
        mysql_query($sqlUpdateLeft, $this->_connect);
        // echo '<br>'.$sqlUpdateLeft;

        $sqlUpdateRight = "UPDATE $this->_table
                            SET rgt = (rgt - $lengthMoveNode)
                            WHERE rgt > $rgtMoveNode";
        mysql_query($sqlUpdateRight, $this->_connect);
        // echo '<br>'.$sqlUpdateRight;

        //4. Lấy thông tin của node cha
        $infoParentNode = $this->getNodeInfo($this->_parent_id);

        //5. Lấy giá trị của node brother
        $infoBrotherNode = $this->getNodeInfo($brother_id);
        $lftBrotherNode = $infoBrotherNode['lft'];

        //6. Cập nhật các giá trị trước khi gắn nhánh vào
        $sqlUpdateLeft = "UPDATE $this->_table
                            SET lft = (lft + $lengthMoveNode)
                            WHERE lft >= $lftBrotherNode";
        mysql_query($sqlUpdateLeft, $this->_connect);
        // echo '<br>'.$sqlUpdateLeft;

        $sqlUpdateRight = "UPDATE $this->_table
                            SET rgt = (rgt + $lengthMoveNode)
                            WHERE rgt > $lftBrotherNode";
        mysql_query($sqlUpdateRight, $this->_connect);
        // echo '<br>'.$sqlUpdateRight;

        //7. Cập nhật level cho nhánh sắp được gán vào cây
        $levelMoveNode = $infoMoveNode['level'];
        $levelParentNode = $infoParentNode['level'];
        $sqlUpdateLevel = "UPDATE $this->_table
                            SET level = (level - $levelMoveNode + $levelParentNode + 1)
                            WHERE rgt <= 0";
        mysql_query($sqlUpdateLevel, $this->_connect);
        // echo '<br>'.$sqlUpdateLevel;

        //8. Cập nhật nhánh trước khi gán vào node mới
        $sqlUpdateLeft = "UPDATE $this->_table
                            SET lft = (lft + {$lftBrotherNode})
                            WHERE rgt <= 0";
        mysql_query($sqlUpdateLeft, $this->_connect);
        // echo '<br>'.$sqlUpdateLeft;

        $sqlUpdateRight = "UPDATE $this->_table
                            SET rgt = (rgt + {$lftBrotherNode} + {$lengthMoveNode} - 1)
                            WHERE rgt <= 0";
        mysql_query($sqlUpdateRight, $this->_connect);
        // echo '<br>'.$sqlUpdateRight;

        //9. Gán vào node cha
        $sqlUpdateNode = "UPDATE $this->_table
                            SET parent = {$infoParentNode['id']}
                            WHERE id = {$infoMoveNode['id']}";
        mysql_query($sqlUpdateNode, $this->_connect);
        // echo '<br>'.$sqlUpdateNode;

    }

    protected function moveAfter($brother_id) {
        $infoMoveNode = $this->getNodeInfo($this->_id);
        $lftMoveNode = $infoMoveNode['lft'];
        $rgtMoveNode = $infoMoveNode['rgt'];

        //1. Tách nhánh khỏi cây
        $sqlSelect = "UPDATE $this->_table
         SET lft = (lft - $lftMoveNode),
             rgt = (rgt - $rgtMoveNode)
         WHERE lft BETWEEN $lftMoveNode AND $rgtMoveNode";
        mysql_query($sqlSelect, $this->_connect);
        // echo '<br>' . $sqlSelect;

        //2. Tính độ dài của nhánh chúng ta cắt
        $lengthMoveNode = $this->lengthNode($lftMoveNode, $rgtMoveNode);

        //3. Cập nhật giá trị các node nằm bên phải của node tách
        $sqlUpdateLeft = "UPDATE $this->_table
                            SET lft = (lft - $lengthMoveNode)
                            WHERE lft > $rgtMoveNode";
        mysql_query($sqlUpdateLeft, $this->_connect);
        // echo '<br>'.$sqlUpdateLeft;

        $sqlUpdateRight = "UPDATE $this->_table
                            SET rgt = (rgt - $lengthMoveNode)
                            WHERE rgt > $rgtMoveNode";
        mysql_query($sqlUpdateRight, $this->_connect);
        // echo '<br>'.$sqlUpdateRight;

        //4. Lấy thông tin của node cha
        $infoParentNode = $this->getNodeInfo($this->_parent_id);

        //5. Lấy giá trị của node brother
        $infoBrotherNode = $this->getNodeInfo($brother_id);
        $rgtBrotherNode = $infoBrotherNode['rgt'];

        //6. Cập nhật các giá trị trước khi gắn nhánh vào
        $sqlUpdateLeft = "UPDATE $this->_table
                            SET lft = (lft + $lengthMoveNode)
                            WHERE lft > $rgtBrotherNode";
        mysql_query($sqlUpdateLeft, $this->_connect);
        // echo '<br>'.$sqlUpdateLeft;

        $sqlUpdateRight = "UPDATE $this->_table
                            SET rgt = (rgt + $lengthMoveNode)
                            WHERE rgt > $rgtBrotherNode";
        mysql_query($sqlUpdateRight, $this->_connect);
        // echo '<br>'.$sqlUpdateRight;

        //7. Cập nhật level cho nhánh sắp được gán vào cây
        $levelMoveNode = $infoMoveNode['level'];
        $levelParentNode = $infoParentNode['level'];
        $sqlUpdateLevel = "UPDATE $this->_table
                            SET level = (level - $levelMoveNode + $levelParentNode + 1)
                            WHERE rgt <= 0";
        mysql_query($sqlUpdateLevel, $this->_connect);
        // echo '<br>'.$sqlUpdateLevel;

        //8. Cập nhật nhánh trước khi gán vào node mới
        $sqlUpdateLeft = "UPDATE $this->_table
                            SET lft = (lft + {$rgtBrotherNode} + 1)
                            WHERE rgt <= 0";
        mysql_query($sqlUpdateLeft, $this->_connect);
        // echo '<br>'.$sqlUpdateLeft;

        $sqlUpdateRight = "UPDATE $this->_table
                            SET rgt = (rgt + {$rgtBrotherNode} + {$lengthMoveNode})
                            WHERE rgt <= 0";
        mysql_query($sqlUpdateRight, $this->_connect);
        // echo '<br>'.$sqlUpdateRight;

        //9. Gán vào node cha
        $sqlUpdateNode = "UPDATE $this->_table
                            SET parent = {$infoParentNode['id']}
                            WHERE id = {$infoMoveNode['id']}";
        mysql_query($sqlUpdateNode, $this->_connect);
        // echo '<br>'.$sqlUpdateNode;

    }

    public function moveUp($id){
        $infoMoveNode = $this->getNodeInfo($id);
        $infoParentNode = $this->getNodeInfo($infoMoveNode['parent']);

        $sql1 = "SELECT *FROM $this->_table 
                WHERE parent = {$infoMoveNode['parent']} AND lft < {$infoMoveNode['lft']} 
                ORDER BY lft DESC LIMIT 1";

        $result1 = mysql_query($sql1, $this->_connect);

        $infoBrotherNode = mysql_fetch_assoc($result1);

        if(!empty($infoBrotherNode)){
            $options = array('position' => 'before', 'brother_id' => $infoBrotherNode['id']);
            $this->moveNode($id, $infoMoveNode['parent'], $options);
        }
    }

    public function moveDown($id){
        $infoMoveNode = $this->getNodeInfo($id);
        $infoParentNode = $this->getNodeInfo($infoMoveNode['parent']);

        $sql1 = "SELECT *FROM $this->_table 
                WHERE parent = {$infoMoveNode['parent']} AND lft > {$infoMoveNode['lft']} 
                ORDER BY lft ASC LIMIT 1";

        $result1 = mysql_query($sql1, $this->_connect);

        $infoBrotherNode = mysql_fetch_assoc($result1);

        if(!empty($infoBrotherNode)){
            $options = array('position' => 'after', 'brother_id' => $infoBrotherNode['id']);
            $this->moveNode($id, $infoMoveNode['parent'], $options);
        }
    }

    public function lengthNode($lftMoveNode, $rgtMoveNode) {
        $lengthMoveNode = $rgtMoveNode - $lftMoveNode + 1;
        return $lengthMoveNode;
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
