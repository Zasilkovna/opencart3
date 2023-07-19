<?php

namespace Packetery\Db;

use Packetery\DAL\DbAdapter;

abstract class BaseRepository
{
    const OPERATOR_AND = 'AND';
    const OPERATOR_OR = 'OR';

    /**
     * @var DbAdapter
     */
    protected $db;

    /**
     * @param DbAdapter $DbAdapter
     */
    public function __construct(DbAdapter $DbAdapter) {
        $this->db = $DbAdapter;
    }

    /**
     * @param string $table
     * @param array $data
     * @return int
     */
    public function insert($table, array $data) {
        $this->db->query('INSERT INTO `' . DB_PREFIX . $table . '` SET ' . $this->db->generateSQLFromData($data));

        return $this->db->getLastId();
    }

    /**
     * @param string $table
     * @param array $data
     * @param array $where
     * @return mixed
     */
    public function update($table, array $data, array $where) {
        return $this->db->query('UPDATE `' . DB_PREFIX . $table . '` SET ' . $this->db->generateSQLFromData($data) . ' WHERE ' . $this->db->generateSQLFromData($where, self::OPERATOR_AND));
    }

    /**
     * @param string $table
     * @param string  $columnName
     * @param mixed $value
     *
     * @return void
     */
    public function delete($table, $columnName, $value) {
        $this->db->query(
            sprintf('DELETE FROM `%s` WHERE %s',
                DB_PREFIX . $table,
                $this->generateSQLFromData([$columnName => $value])
            )
        );
    }
}
