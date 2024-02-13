<?php

class Database {

    private $conn;
    public $lastInsertId    = 0;

    public function __construct($table=false) {

        $servername = 'localhost';
        $dbname = 'delivery';
        $username = 'root';
        $password = '';

        try {
            $this->conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch(PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }

        $this->setTable($table);
    }

    public function setTable($table=false) {
        if (!$table) {
            return;
        }
        $this->table = $table;
    }

    public function select($where=false, $order=false, $sql=false, $params=array(), $limit=false) {
        if ($sql) {
            $this->query    = $sql;
        } else {
            $this->query	= "SELECT ".$this->table.".* FROM ".$this->table;
            $this->query    .= ($where == false) ? "" : " WHERE ".$where;
            $this->query    .= ($order == false) ? "" : " ORDER BY ".$order;
            $this->query    .= ($limit == false) ? "" : " LIMIT ".$limit;
        }

        $this->execute($params);
        $this->numRows      = (isset($this->result)) ? $this->result->rowCount() : 0;
    }

    public function selectFetchOne($where=false, $order=false, $sql=false, $params=array(), $limit=false) {
        if ($sql) {
            $this->query    = $sql;
        } else {
            $this->query    = "SELECT ".$this->table.".* FROM ".$this->table;
            $this->query    .= ($where == false) ? "" : " WHERE ".$where;
            $this->query    .= ($order == false) ? "" : " ORDER BY ".$order;
            $this->query    .= ($limit == false) ? "" : " LIMIT ".$limit;
        }

        $this->execute($params);
        $this->numRows = $this->result->rowCount();
        return $this->getRow(true);  // returns array
    }   

    public function insert($table, $params, $ignore = null) {
        if (count($params) == 0) {
            return;
        }

        $values		= "";
        $keys		= "";

        foreach ($params as $key => $val) {
            $keys	.= $key.", ";
            $values	.= ":$key, ";
        }

        $keys			= rtrim($keys,", ");
        $values			= rtrim($values,", ");
        
        $ignoreClause = ($ignore) ? 'IGNORE' : '';

        $this->query	= "INSERT $ignoreClause INTO ".$table." (".$keys.") VALUES (".$values.")";
        $this->execute($params);
        $this->lastInsertId = (isset($this->conn)) ? $this->conn->lastInsertId() : 0;
        return $this->lastInsertId;
    }

    public function update($fields=array(), $where=false, $limit=false, $params=array()) {
        if (count($fields) == 0) {
            return;
        }

        $qStr	= "";
        $data = array();
        foreach ($fields as $key => $val) {
            if (is_array($val)) {
                if (isset($val['function'])) {
                    $fn = $val['function']."(";
                    $fnParams = '';
                    if (isset($val['params'])) {
                        if (count($val['params']) > 0) {
                            foreach($val['params'] as $k => $v) {
                                $param = $key.$k;
                                $fnParams .= ($fnParams == '') ? ":$param" : ", :$param";
                                $data[$param] = $v;
                            }
                        }
                    }
                    $fn .= "$fnParams)";
                    $qStr .= "$key = $fn, ";
                    unset($params[$key]);
                }
            } else {
                $qStr .= "$key = :$key, ";
                $data[$key] = $params[$key];
                unset($params[$key]);
            }
        }

        $qStr			= rtrim($qStr, ", ");

        $this->query	= "UPDATE ".$this->table." SET ".$qStr;
        if ($where) {
            $this->query	.= " WHERE ".$where;
        }

        if ($limit) {
            $this->query      .= " LIMIT $limit";
        }

        $merged = array_merge($data, $params);

        $this->execute($merged);
        $this->rowsAffected = isset($this->result) ? $this->result->rowCount() : 0;
        return $this->rowsAffected;
    }

    public function delete($where=false, $limit=false, $params=array()) {
        $this->query = "DELETE FROM ".$this->table;
        if ($where) {
            $this->query    .= " WHERE ".$where;
        }
        if ($limit) {
            $this->query    .= " LIMIT 1";
        }
        $this->execute($params);
        $this->rowsAffected = isset($this->result) ? $this->result->rowCount() : 0;
}

    private function prepareQuery() {
        if (!isset($this->conn) || !method_exists($this->conn, 'prepare')) {
            throw new Exception('FATAL: Calling prepare on a non-object in db.class.php in function execute()');
        }
        $this->result       = $this->conn->prepare($this->query);
    }

    public function execute($params=null) {
        try {
            $this->prepareQuery();
            $this->result->execute($params);
        } catch (PDOException $e) {
            $this->executeRetry($e, $params);
            $script = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';
            $script .= ' :: '.$this->conn->getAttribute(PDO::ATTR_SERVER_INFO);
            $this->errorInfo = $e; 
        } catch (Exception $e) {
            error_log($e->getMessage(), 0);
        }
    }

    private function executeRetry($err, $params) {
        $errMessage = $err->getMessage();
        return $errMessage;
        // if (strpos($errMessage, 'MySQL server has gone away') !== false) {
        //     try {
        //         $this->connect();
        //         $this->prepareQuery();
        //         $this->result->execute($params);
        //     } catch (PDOException $e) {
        //         mail('developer@toysrus.com.au', 'Second try failure', $e->getMessage().PHP_EOL.PHP_EOL.$this->query);
        //     }
        // }
        return true;
    }

    public function getRow($arrayOnly=false) {
        $this->row      = array();
        try {
            if (!isset($this->result)) {
                throw new Exception('FATAL: PDO result object not set when fetching row in getRow()');
            }
            $this->row  = $this->result->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $Exception) {
            return array();
        } catch (Exception $e) {
            return array();
        }

        if ($arrayOnly) {
            return $this->row;
        }
        if ($this->row !== false) {
            foreach($this->row as $key => $val) {
                $this->$key		= $val;
            }
        }
        return $this->row;
    }


    public function getAllRows() {
        $this->rowset     = array();
        try {
            if (!isset($this->result)) {
                throw new Exception('FATAL: PDO result object not set when fetching all rows in getAllRows()');
            }
            $this->rowset = $this->result->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $Exception) {
            return array();
        } catch (Exception $e) {
            return array();
        }        

        return $this->rowset;
    }
}
?>