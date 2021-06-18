<?php

namespace Packetery\Carrier;

class CarrierQuery extends \Packetery\Db\BaseQuery
{
    /**
     * @return string
     */
    protected function getMainTable() {
        return 'zasilkovna_carrier';
    }

    /**
     * @param bool $deleted
     */
    public function whereDeleted($deleted) {
        $this->andWhere[] = function () use ($deleted) {
            return 'main_table.deleted = ' . ($deleted ? '1' : '0');
        };
    }

    /**
     * @param string $name
     * @param bool $left
     * @param bool $right
     */
    public function whereNameLike($name, $left = true, $right = true) {
        $this->andWhere[] = function () use ($name, $left, $right) {
            $baseLike = $this->createLikeValue($name, $left, $right);
            return 'main_table.name LIKE ' . $this->finalizeString($baseLike);
        };
    }

    /**
     * @param string $value
     */
    public function whereCountry($value) {
        $this->andWhere[] = function () use ($value) {
            return 'main_table.country = ' . $this->finalizeString($value);
        };
    }

    /**
     * @param string $value
     */
    public function whereCurrency($value) {
        $this->andWhere[] = function () use ($value) {
            return 'main_table.currency = ' . $this->finalizeString($value);
        };
    }

    /**
     * @param float $value
     */
    public function whereMaxWeightTo($value) {
        $this->andWhere[] = function () use ($value) {
            return 'main_table.max_weight <= ' . $this->db->escape((float)$value);
        };
    }

    /**
     * @param bool $value
     */
    public function whereIsPickupPoints($value) {
        $this->andWhere[] = function () use ($value) {
            return 'main_table.is_pickup_points = ' . ($value ? '1' : '0');
        };
    }

    /**
     * @param bool $value
     */
    public function whereHasCarrierDirectLabel($value) {
        $this->andWhere[] = function () use ($value) {
            return 'main_table.has_carrier_direct_label = ' . ($value ? '1' : '0');
        };
    }

    /**
     * @param bool $value
     */
    public function whereCustomsDeclarations($value) {
        $this->andWhere[] = function () use ($value) {
            return 'main_table.customs_declarations = ' . ($value ? '1' : '0');
        };
    }
}
