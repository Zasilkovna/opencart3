<?php

namespace Packetery\Db;

/**
 * @property-read array $row
 * @property-read array $rows
 * @property-read int $num_rows
 */
class Result
{
    /**
     * @var \stdClass
     */
    private $_result;

    /**
     * Result constructor.
     *
     * @param \stdClass $result
     */
    public function __construct(\stdClass $result) {
        $this->_result = $result;
    }

    public function __isset($name) {
        return isset($this->_result->$name);
    }

    public function __get($name) {
        return $this->_result->$name;
    }
}
