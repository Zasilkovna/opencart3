<?php

namespace Packetery\Db;

abstract class BaseQuery
{
    /** @var \DB */
    protected $db;

    /** @var array */
    protected $andWhere = [];

    /** @var array */
    protected $orderBy = [];

    /**
     * CarrierQO constructor.
     *
     * @param \DB $db
     */
    public function __construct(\DB $db) {
        $this->db = $db;
    }

    /**
     * @return string
     */
    abstract protected function getMainTable();

    /**
     * @return string
     */
    private function getFinalMainTable() {
        return DB_PREFIX . $this->getMainTable();
    }

    /**
     * @param string $field
     * @param string $way
     */
    public function addOrderBy($field, $way) {
        $this->orderBy[] = function () use ($field, $way) {
            return "$field $way";
        };
    }

    /**
     * @return string
     */
    private function createSql() {
        $query = "SELECT main_table.* FROM `{$this->getFinalMainTable()}` AS main_table";

        if (!empty($this->andWhere)) {
            $query .= ' WHERE ';
        }

        $andWheres = [];
        foreach ($this->andWhere as $item) {
            $andWheres[] = call_user_func_array($item, []);
        }

        $query .= implode(' AND ', $andWheres);

        if (!empty($this->orderBy)) {
            $query .= ' ORDER BY ';
        }

        $orderBys = [];
        foreach ($this->orderBy as $item) {
            $orderBys[] = call_user_func_array($item, []);
        }

        $query .= implode(', ', $orderBys);

        return $query;
    }

    /**
     * @return \Packetery\Db\Result
     */
    public function getResult() {
        return new Result($this->db->query($this->createSql()));
    }

    /**
     * @param string $value
     * @return string
     */
    protected function quoteString($value) {
        return "'" . $value . "'";
    }

    /**
     * @param string $value
     * @return string
     */
    protected function finalizeString($value) {
        return $this->quoteString($this->db->escape($value));
    }

    /**
     * @param string $value
     * @param bool $left
     * @param bool $right
     * @return string
     */
    protected function createLikeValue($value, $left = true, $right = true) {
        $baseLike = $value;

        if ($left) {
            $baseLike = '%' . $baseLike;
        }

        if ($right) {
            $baseLike = $baseLike . '%';
        }

        return $baseLike;
    }
}
