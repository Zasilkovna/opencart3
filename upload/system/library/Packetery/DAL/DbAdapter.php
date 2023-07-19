<?php

namespace Packetery\DAL;
use \DB;

class DbAdapter {

    /** @var DB */
    public $db;

    /** @var TablePrefixer */
    private $tablePrefixer;

    /**
     * @throws \Exception
     */
    public function __construct(DB $db, TablePrefixer $tablePrefixer) {
        $this->db = $db;
        $this->tablePrefixer = $tablePrefixer;
    }

    /**
     * @param string $sql
     * @return mixed
     */
    public function query($sql) {
        return $this->db->query($sql);
    }

    /**
     * @param string $sql
     * @param mixed ...$args
     * @return Result
     */
    public function queryResult($sql, ...$args) {
        $sql = $this->prepareSql($sql, $args);
        $result = $this->db->query($sql);

        return new Result($result);
    }

    /**
     * @param string $key
     * @return string
     */
    public function escape($key) {
        return $this->db->escape($key);
    }

    /**
     * @return int
     */
    public function getLastId() {
        return (int)$this->db->getLastId();
    }

    /**
     * @return array
     */
    public function getLog() {
        $db = $this->db;

        return $db::$log;
    }

    /**
     * @param string $sql
     * @param array $params
     * @return string
     */
    private function prepareSql($sql, array $params) {
        foreach ($params as $param) {
            $valueEscaped = $this->valueEscape($param);
            $sql = preg_replace('/\?/', $valueEscaped, $sql, 1);
        }
        return $this->tablePrefixer->prefix($sql);
    }

    /**
     * @param mixed $value
     */
    private function valueEscape($value) {
        switch (true) {
            case (is_int($value) || is_float($value)):
                $valueEscaped = $value;
                break;
            case is_bool($value):
                $valueEscaped = var_export($value, true);
                break;
            case is_null($value):
                $valueEscaped = 'NULL';
                break;
            default:
                $valueEscaped = '\'' . $this->db->escape((string)$value) . '\'';
        }

        return $valueEscaped;
    }

    /**
     * @param array $data associative array of data, it supports integer, float, boolean, null and string values
     * @param string $separator
     * @return string array stringified to SQL
     */
    public function generateSQLFromData(array $data, $separator = ',') {
        $sqlParts = [];
        foreach ($data as $key => $value) {
            $valueEscaped = $this->valueEscape($value);
            $sqlParts[] = sprintf(' `%s` = %s', $this->escape($key), $valueEscaped);
        }
        return implode($separator, $sqlParts);
    }
}
