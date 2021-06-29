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
	public function getFilteredSorted($filter)
	{
		$whereClause = '';
		list($whereConditions, $ordering) = $this->getConditions($filter);
		if ($whereConditions) {
			$whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
		}

		/** @var StdClass $queryResult */
		$queryResult = $this->db->query(
			'SELECT `name`, `country`, `currency`, `max_weight`, `is_pickup_points`, `has_carrier_direct_label`,
				`customs_declarations`  FROM `' . DB_PREFIX . 'zasilkovna_carrier`
			' . $whereClause . '
			ORDER BY ' . $ordering . '
			');
		return $queryResult->rows;
	}

	/**
	 * @param array $filter
	 * @return array
	 */
	private function getConditions(array $filter)
	{
		$whereConditions = [];
		$orderColumn = 'name';
		$direction = 'ASC';
		foreach ($filter as $filterParam => $filterValue) {
			if ($filterParam === 'orderColumn') {
				$orderColumn = $filterValue;
			} else if ($filterParam === 'direction') {
				$direction = $filterValue;
			} else if (preg_match('/^[_a-z]+$/', $filterParam)) {
				if ($filterValue !== '') {
					if (in_array($filterParam, $this->likeFilters, true)) {
						$whereConditions[] = ' `' . $filterParam . '` LIKE "%' . $this->db->escape($filterValue) . '%"';
					} else if (in_array($filterParam, $this->exactFilters, true)) {
						$whereConditions[] = ' `' . $filterParam . '` = "' . $this->db->escape($filterValue) . '"';
					}
				} else if (((int)$filterValue !== 0) && in_array($filterParam, $this->maxFilters, true)) {
					$whereConditions[] = ' `' . $filterParam . '` <= "' . $this->db->escape($filterValue) . '"';
				}
			}
		}
		$ordering = '`' . $orderColumn . '` ' . $direction;
		return [$whereConditions, $ordering];
	}

}
