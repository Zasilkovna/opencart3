<?php

namespace Packetery\Carrier;

use Packetery\Db\BaseRepository;
use StdClass;

class CarrierRepository extends BaseRepository
{
    /** @var string[] */
    public $viewColumns = [
        'name',
        'country',
        'currency',
        'max_weight',
        'is_pickup_points',
        'has_carrier_direct_label',
        'customs_declarations',
    ];

    /** @var string[] */
    private $likeFilters = [
        'name',
    ];

    /** @var string[] */
    private $maxFilters = [
        'max_weight',
    ];

    /** @var string[] */
    private $exactFilters = [
        'country',
        'currency',
        'is_pickup_points',
        'has_carrier_direct_label',
        'customs_declarations',
    ];

    /**
     * @return stdClass
     */
    public function getCarrierIds()
    {
        return $this->db->query('SELECT `id` FROM `' . DB_PREFIX . 'zasilkovna_carrier`');
    }

    /**
     * Set those not in feed as deleted.
     * @param array $carriersInFeed
     * @return void
     */
    public function setOthersAsDeleted(array $carriersInFeed)
    {
        $query = sprintf(
            'UPDATE `' . DB_PREFIX . 'zasilkovna_carrier` SET `deleted` = 1 WHERE `id` NOT IN (%s)',
            implode(',', $carriersInFeed)
        );
        $this->db->query($query);
    }

    /**
     * @param array $filter
     * @return array
     */
    public function getFilteredSorted(array $filter)
    {
        list($whereConditions, $ordering) = $this->getConditionsAndOrdering($filter);
        $whereClause = '';
        if ($whereConditions) {
            $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
        }

        $query = "SELECT `id`, `name`, `country`, `currency`, `max_weight`, `is_pickup_points`, 
          `has_carrier_direct_label`, `customs_declarations`
          FROM `" . DB_PREFIX . "zasilkovna_carrier`
          $whereClause
          ORDER BY $ordering";

        $queryResult = $this->db->query($query);

        return $queryResult->rows;
    }

    /**
     * @param array $filter
     * @return array
     */
    public function setDefaultOrdering(array $filter)
    {
        if (!isset($filter['orderColumn']) ||
            !in_array($filter['orderColumn'], $this->viewColumns, true)
        ) {
            $filter['orderColumn'] = 'name';
        }

        if (!isset($filter['direction']) ||
            !in_array($filter['direction'], ['ASC', 'DESC'])
        ) {
            $filter['direction'] = 'ASC';
        }

        return $filter;
    }

    /**
     * @param array $filter
     * @return array
     */
    private function getConditionsAndOrdering(array $filter)
    {
        $whereConditions = [];
        $orderColumn = 'name';
        $direction = 'ASC';
        foreach ($filter as $filterParam => $filterValue) {
            // validation is done in setDefaultOrdering
            if ($filterParam === 'orderColumn') {
                $orderColumn = $filterValue;
            } elseif ($filterParam === 'direction') {
                $direction = $filterValue;
            } else {
                $whereConditions = $this->prepareWhereConditions($filterParam, $filterValue, $whereConditions);
            }
        }
        $ordering = '`' . $orderColumn . '` ' . $direction;

        return [$whereConditions, $ordering];
    }

    /**
     * @param string $columnName
     * @param string $filterValue
     * @param array $whereConditions
     * @return array
     */
    private function prepareWhereConditions($columnName, $filterValue, array $whereConditions)
    {
        if ($filterValue !== '') {
            if (in_array($columnName, $this->likeFilters, true)) {
                $whereConditions[] = ' `' . $columnName . '` LIKE "%' . $this->db->escape($filterValue) . '%"';
            } elseif (in_array($columnName, $this->exactFilters, true)) {
                $whereConditions[] = ' `' . $columnName . '` = "' . $this->db->escape($filterValue) . '"';
            }
        }
        if (((int)$filterValue !== 0) && in_array($columnName, $this->maxFilters, true)) {
            $whereConditions[] = ' `' . $columnName . '` <= "' . $this->db->escape($filterValue) . '"';
        }

        return $whereConditions;
    }

    /**
     * @return string[]
     */
    public function getOcCountries()
    {
        $rows = $this->db->query('SELECT `iso_code_2`, `name` FROM `' . DB_PREFIX . 'country`')->rows;

        return array_column($rows, 'name', 'iso_code_2');
    }

    /**
     * Returns country codes of countries where Packeta delivers
     *
     * @return string[]
     */
    public function getCountries()
    {
        $query = 'SELECT DISTINCT `country` FROM `'
            . DB_PREFIX
            . 'zasilkovna_carrier` WHERE `deleted` = false ORDER BY `country`';

        $countries = $this->db->query($query);

        return array_column(($countries->rows ?: []), 'country');
    }

    /**
     * @return string[]
     */
    public function getZpointCountryCodes()
    {
        return [
          'cz',
          'sk',
          'hu',
          'ro',
        ];
    }

    /**
     * @param string $countryCode
     *
     * @return array
     */
    public function getCarriersByCountry($countryCode)
    {
        $result = $this->getFilteredSorted([
            'country' => $countryCode,
            'deleted' => 0,
        ]);

        return array_column($result, null, 'id');
    }

    /**
     * @return bool
     */
    public function isCarrierTableEmpty()
    {
        $sql = ('SELECT 1 FROM zasilkovna_carrier');

        return $this->db->queryResult($sql)->fetchSingle() !== '1';
    }
}
