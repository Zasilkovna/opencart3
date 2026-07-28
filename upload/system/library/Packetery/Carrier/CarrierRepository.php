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
		'enabled',
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
	public function getFeedCarrierIds()
	{
		return $this->db->query(
			'SELECT `id` FROM `' . DB_PREFIX . 'zasilkovna_carrier` WHERE `source` = \'feed\''
		);
	}

	/**
	 * Set those not in feed as deleted.
	 * @param array $carriersInFeed
	 */
	public function setOtherFeedCarriersAsDeleted($carriersInFeed)
	{
		if ($carriersInFeed === []) {
			$this->db->query(
				'UPDATE `' . DB_PREFIX . 'zasilkovna_carrier` SET `deleted` = 1 WHERE `source` = \'feed\''
			);
			return;
		}

		$carrierIds = implode(',', array_map('intval', $carriersInFeed));
		$this->db->query(
			'UPDATE `' . DB_PREFIX . 'zasilkovna_carrier` SET `deleted` = 1 WHERE `source` = \'feed\' AND `id` NOT IN (' . $carrierIds . ')'
		);
	}

	/**
	 * @param array $filter
	 * @return array<int, array{
	 *   id_record: int,
	 *   name: string,
	 *   country: string,
	 *   currency: string,
	 *   max_weight: string,
	 *   is_pickup_points: string,
	 *   has_carrier_direct_label: string,
	 *   customs_declarations: string,
	 *   enabled: numeric-string
	 * }>
	 */
	public function getFilteredSorted(array $filter)
	{
		list($whereConditions, $ordering) = $this->getConditionsAndOrdering($filter);
		array_unshift($whereConditions, 'c.`available` = 1', 'c.`deleted` = 0');
		$whereClause = '';
		if ($whereConditions) {
			$whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
		}

		$ruleTable = DB_PREFIX . ShippingRuleRepository::TABLE_CARRIER_SHIPPING_RULE;

		/** @var StdClass $queryResult */
		$queryResult = $this->db->query(
			"SELECT
			 c.`id_record`,
			 c.`name`,
			 c.`country`,
			 c.`currency`,
			 c.`max_weight`,
			 c.`is_pickup_points`,
			 c.`has_carrier_direct_label`,
			 c.`customs_declarations`,
			 (CASE WHEN sr.`is_enabled` = 1 THEN 1 ELSE 0 END) AS `enabled`
			 FROM `" . DB_PREFIX . "zasilkovna_carrier` c
			 LEFT JOIN `" . $ruleTable . "` sr ON sr.`carrier_id` = c.`id_record`
			 $whereClause
			 ORDER BY $ordering"
		);
		return $queryResult->rows;
	}

	/**
	 * @param int $idRecord
	 * @return Carrier|null
	 */
	public function findByIdRecord($idRecord)
	{
		/** @var StdClass $queryResult */
		$queryResult = $this->db->query(
			"SELECT `id_record`, `id`, `name`, `country`, `currency`, `max_weight`, `is_pickup_points`,
			 `has_carrier_direct_label`, `customs_declarations`, `available`, `deleted`, `vendor_groups`
			 FROM `" . DB_PREFIX . "zasilkovna_carrier`
			 WHERE `id_record` = " . (int)$idRecord . "
			 LIMIT 1"
		);

		if (empty($queryResult->rows)) {
			return null;
		}

		return $this->mapRowToCarrier($queryResult->row);
	}

	/**
	 * @param array{
	 *   id_record: int,
	 *   id: ?int,
	 *   name: string,
	 *   country: string,
	 *   currency: string,
	 *   max_weight: string|int|float,
	 *   is_pickup_points: string|int|bool,
	 *   has_carrier_direct_label: string|int|bool,
	 *   customs_declarations: string|int|bool,
	 *   available: string|int|bool,
	 *   deleted: string|int|bool,
	 *   vendor_groups: ?string
	 * } $row
	 * @return Carrier
	 */
	private function mapRowToCarrier(array $row)
	{
		return new Carrier(
			(int)$row['id_record'],
			($row['id'] === null ? null : (int)$row['id']),
			(string)$row['name'],
			(string)$row['country'],
			(string)$row['currency'],
			(float)$row['max_weight'],
			(bool)$row['is_pickup_points'],
			(bool)$row['has_carrier_direct_label'],
			(bool)$row['customs_declarations'],
			(bool)$row['available'],
			(bool)$row['deleted'],
			($row['vendor_groups'] === null ? null : json_decode($row['vendor_groups'], true))
		);
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
	 * @return array{0: list<string>, 1: string}
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

		$ordering = "c.`{$orderColumn}` {$direction}";
		if ($orderColumn === 'enabled') {
			$ordering = "`enabled` {$direction}";
		}
		return [$whereConditions, $ordering];
	}

	/**
	 * `enabled` filters on sr.is_enabled because the SELECT alias is not usable in WHERE
	 *
	 * @param string $columnName
	 * @param string $filterValue
	 * @param list<string> $whereConditions
	 * @return list<string>
	 */
	private function prepareWhereConditions($columnName, $filterValue, array $whereConditions)
	{
		if ($columnName === 'enabled') {
			if ($filterValue === '1') {
				$whereConditions[] = ' sr.`is_enabled` = 1';
			} else if ($filterValue === '0') {
				$whereConditions[] = ' (sr.`is_enabled` IS NULL OR sr.`is_enabled` = 0)';
			}
			return $whereConditions;
		}
		if ($filterValue !== '') {
			if (in_array($columnName, $this->likeFilters, true)) {
				$whereConditions[] = ' c.`' . $columnName . '` LIKE "%' . $this->db->escape($filterValue) . '%"';
			} else if (in_array($columnName, $this->exactFilters, true)) {
				$whereConditions[] = ' c.`' . $columnName . '` = "' . $this->db->escape($filterValue) . '"';
			}
		}
		if (((int)$filterValue !== 0) && in_array($columnName, $this->maxFilters, true)) {
			$whereConditions[] = ' c.`' . $columnName . '` <= "' . $this->db->escape($filterValue) . '"';
		}
		return $whereConditions;
	}

}
