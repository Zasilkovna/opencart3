<?php

namespace Packetery\Carrier;

use DB;
use StdClass;

class CarrierRepository
{

	/** @var DB */
	private $db;

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

	public function __construct($db)
	{
		$this->db = $db;
	}

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
	 */
	public function setOthersAsDeleted($carriersInFeed)
	{
		$this->db->query(sprintf('UPDATE `' . DB_PREFIX . 'zasilkovna_carrier` SET `deleted` = 1 WHERE `id` NOT IN (%s)', implode(',', $carriersInFeed)));
	}

	/**
	 * @param array $filter
	 * @return mixed
	 */
	public function getFilteredSorted(array $filter)
	{
		list($whereConditions, $ordering) = $this->getConditionsAndOrdering($filter);
		$whereClause = '';
		if ($whereConditions) {
			$whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
		}

		/** @var StdClass $queryResult */
		$queryResult = $this->db->query(
			"SELECT `name`, `country`, `currency`, `max_weight`, `is_pickup_points`, `has_carrier_direct_label`, `customs_declarations`
			 FROM `" . DB_PREFIX . "zasilkovna_carrier`
			 $whereClause
			 ORDER BY $ordering"
		);
		return $queryResult->rows;
	}

	/**
	 * @param array $filter
	 * @return array
	 */
	public function setDefaultOrdering(array $filter)
	{
		if (
			!isset($filter['orderColumn']) ||
			!in_array($filter['orderColumn'], $this->viewColumns, true)
		) {
			$filter['orderColumn'] = 'name';
		}

		if (
			!isset($filter['direction']) ||
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
			} else if ($filterParam === 'direction') {
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
			} else if (in_array($columnName, $this->exactFilters, true)) {
				$whereConditions[] = ' `' . $columnName . '` = "' . $this->db->escape($filterValue) . '"';
			}
		}
		if (((int)$filterValue !== 0) && in_array($columnName, $this->maxFilters, true)) {
			$whereConditions[] = ' `' . $columnName . '` <= "' . $this->db->escape($filterValue) . '"';
		}
		return $whereConditions;
	}

	private function getAllActiveCountries()
	{
		$sql = sprintf('
			SELECT DISTINCT
				UPPER(`zc`.`country`) AS `iso2`,
				`c`.`name` FROM `%s` `zc`
			LEFT JOIN `%s` `c` ON UPPER(`zc`.`country`) = `c`.`iso_code_2`
			WHERE `deleted` = 0
			ORDER BY `c`.`name`',
			DB_PREFIX . 'zasilkovna_carrier',
			DB_PREFIX . 'country');

		return $this->db->query($sql);
	}

	public function getAllActiveCountriesAssoc()
	{
		$queryResult = $this->getAllActiveCountries();
		$countriesAssoc = [];
		foreach ($queryResult->rows as $row) {
			$countriesAssoc[$row['iso2']] = $row['name'];
		}

		return $countriesAssoc;
	}

	/**
	 * @param array $putFirstIsos
	 * @param array $countries
	 *
	 * @return array
	 */
	public function reorderCountries($putFirstIsos, $countries)
	{
		asort($countries, SORT_STRING);
		$beginning= [];
		foreach ($putFirstIsos as $firstIso) {
			$beginning[$firstIso] = $countries[$firstIso];
			unset ($countries[$firstIso]);
		}

		return $beginning + $countries;
	}
}
