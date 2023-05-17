<?php

namespace Packetery\DAL;

class Result {

	/** @var \stdClass */
    private $result;

    public function __construct(\stdClass $result) {
        $this->result = $result;
    }

	/**
	 * @return array
	 */
	public function fetchAll() {
		return $this->result->rows;
	}

	/**
	 * @return array|null
	 */
	public function fetch() {
		$row = $this->result->row;

		if (empty($row)) {
			return null;
		}

		return $row;
	}

	/**
	 * @return mixed|null
	 */
	public function fetchSingle() {
		$row = $this->result->row;

		if (empty($row)) {
			return null;
		}

		return reset($row);
	}

	/**
     * @param string $key
     * @return array
     */
    final public function fetchAssoc($key) {
        $rows = [];
        foreach ($this->fetchAll() as $row) {
            $rows[$row[$key]] = $row;
        }

        return $rows;
    }

}

