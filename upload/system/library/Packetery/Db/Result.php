<?php

namespace Packetery\Db;

class Result
{
	/**
	 * @var \stdClass
	 */
	private $result;

	/**
	 * Result constructor.
	 *
	 * @param \stdClass $result
	 */
	public function __construct(\stdClass $result) {
		$this->result = $result;
	}

	/**
	 * @return array
	 */
	public function getRows() {
		return $this->result->rows;
	}
}
