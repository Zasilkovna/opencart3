<?php

namespace Packetery\Db;

use \DB;

class BaseRepository
{
	/** @var DB */
	protected $db;

	/**
	 * @param DB $db
	 */
	public function __construct(DB $db)
	{
		$this->db = $db;
	}

	/**
	 * @param array $data associative array of data, it supports integer, float, boolean, null and string values
	 * @return string array stringified to SQL
	 */
	public function generateSQLFromData(array $data)
	{
		$sqlParts = [];
		foreach ($data as $key => $value) {
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
			$sqlParts[] = sprintf(' `%s` = %s', $this->db->escape($key), $valueEscaped);
		}
		return implode(',', $sqlParts);
	}

	/**
	 * @param string $table
	 * @param array $data
	 * @return int
	 */
	public function insert($table, array $data)
	{
		$this->db->query('INSERT INTO `' . DB_PREFIX . $table . '` SET ' . $this->generateSQLFromData($data));

		return $this->db->getLastId();
	}

	/**
	 * @param string $table
	 * @param array $data
	 * @param string $where
	 * @return mixed
	 */
	public function update($table, array $data, $where)
	{
		return $this->db->query('UPDATE `' . DB_PREFIX . $table . '` SET ' . $this->generateSQLFromData($data) . ' WHERE ' . $where);
	}
}
