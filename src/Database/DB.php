<?php

namespace Melody\Database;

use PDO;

class DB {
    private static $currentInstance = null;

    private $selects = ['*'];
    private $table = '';
    private $wheres = [];
    private $model = null;

    private static function getInstance()
    {
        if (self::$currentInstance === null) {
            self::$currentInstance = new DB;
        }

        return self::$currentInstance;
    }

    //SELECT * FROM table WHERE id = ? OR WHERE id > 0 
    protected function buildQuery() 
    {
        $selectStmt = implode(',', $this->selects);
        $whereValues = [];
        $whereStmts = [];
        foreach ($this->wheres as $index => $where) {
            $stmtString = '';
            if ($index > 0) {
                $stmtString .= ' ' .$where['boolean'] .' ';
            } else {
                $stmtString .= ' WHERE ';
            }
            $stmtString .= $where['column'] .' ' .$where['operator'] .' ?';
            $whereValues[] = $where['value'];
            $whereStmts[] = $stmtString;
        }
        $wheresStmt = implode(' ', $whereStmts);
        $sql = "SELECT $selectStmt FROM {$this->table}{$wheresStmt}";

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($whereValues);

        return $stmt;
    }

    private function connect() 
    {
        $config = include('./config.php');
        $dbConfig = $config['db'];

        $driver = $dbConfig['driver'];
        $host = $dbConfig['host'];
        $port = $dbConfig['port'];
        $db = $dbConfig['db'];
        $username = $dbConfig['username'];
        $password = $dbConfig['password'];
        $connectionString = "$driver:host=$host:$port;dbname=$db";

        return new PDO($connectionString, $username, $password, $dbConfig['options']);
    }

    public function first()
    {
        $stmt = $this->buildQuery();

        $result = $stmt->fetch();

        if ($result) {
            if ($this->model) {
                $modelClass = $this->model;
                return $modelClass::create($result);
            }
    
            return $result;
        }

        return null;
    }

    public function last()
    {
        
    }

    public function get()
    {
        $stmt = $this->buildQuery();

        $results = $stmt->fetchAll();

        if ($this->model) {
            $modelClass = $this->model;
            return $modelClass::from($results);
        }
        return $results;
    }

    public function addSelect($column)
    {
        if (is_array($column)) {
            $this->selects = $column;
        } else {
            if (count($this->selects) == 1 && $this->selects[0] == '*') {
                $this->selects = [$column];
            } else {
                $this->selects[] = $column;
            }
        }

        return $this;
    }

    public function addModel($modelClass)
    {
        $this->model = $modelClass;

        return $this;
    }

    public function addTable($table)
    {
        $this->table = $table;

        return $this;
    }
    
    public function addWhere($column, $operator = '=', $value = '', $boolean = 'AND') 
    {
        $this->wheres[] = ['column' => $column, 'operator' => $operator, 'value' => $value, 'boolean' => $boolean];

        return $this;
    }

    public static function __callStatic($name, $arguments)
    {
        return self::resolveName($name, $arguments);
    }

    public function __call($name, $arguments)
    {
        return self::resolveName($name, $arguments);
    }

    private static function resolveName($name, $arguments)
    {
        $completeName = '';
        $additionalArgs = [];

        if ($name == 'select' || $name == 'table' || $name == 'where' || $name == 'model') {
            $completeName = 'add' .ucfirst($name);
        } else if (str_starts_with($name, 'or')) {
            $completeName = 'add' .ucfirst(strtolower(str_replace('or', '', $name)));
            $additionalArgs[] = 'OR';
        }
        if ($completeName) {
            return self::getInstance()->$completeName(...$arguments, ...$additionalArgs);
        }
    }

}