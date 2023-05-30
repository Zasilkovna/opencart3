<?php

namespace Packetery\Vendor;

use DB;

class VendorRepository {

	const PACKETA_VENDORS = [
		['id' => 'zpoint', 'name' => 'vendor_add_zpoint', 'countries' => ['cz', 'sk', 'hu', 'ro']],
		['id' => 'zbox', 'name' => 'vendor_add_zbox', 'countries' => ['cz', 'sk', 'hu', 'ro']],
		['id' => 'alzabox', 'name' => 'vendor_add_alzabox', 'countries' => ['cz']],
	];

	/** @var DB */
	private $db;

	/**
	 * @param DB $db
	 */
	public function __construct(DB $db) {
		$this->db = $db;
	}

	/**
	 * @param string $country
	 * @param bool $onlyEnabled
	 *
	 * @return null|array
	 */
	public function getVendorsByCountry($country, $onlyEnabled = false) {
		$countryCode = $this->db->escape($country);

		$additionalWhere = '';
		if ($onlyEnabled) {
			$additionalWhere = ' AND `zv`.`is_enabled` = 1 ';
		}

		$query = sprintf(
			"SELECT `zv`.`carrier_id`,
				`zv`.`id` AS `vendor_id`,
				`zc`.`name`,
				`zv`.`carrier_name_cart`,
				`zv`.`group`,
				COALESCE(`zv`.`country`, `zc`.`country`) AS `country`,
				IF(ISNULL(`zv`.`carrier_id`), 1, `zc`.`is_pickup_points`) AS 'has_pickup_points'
			FROM `%s` `zv` 
			LEFT JOIN `%s` `zc` ON `zv`.`carrier_id` = `zc`.`id`
			WHERE (`zv`.`country` = '%s' OR `zc`.`country` = '%s') %s
			",
			DB_PREFIX . 'zasilkovna_vendor',
			DB_PREFIX . 'zasilkovna_carrier',
			$countryCode,
			$countryCode,
			$additionalWhere
		);

		$queryResult = $this->db->query($query);

		if ($queryResult->num_rows === 0) {
			return null;
		}

		return $queryResult->rows;
	}

	/**
	 * @param string $countryCode
	 *
	 * @return array
	 */
	public function getInternalVendorsByCountry($countryCode) {
		return array_filter(self::PACKETA_VENDORS,
			static function ($vendor) use ($countryCode) {
				return in_array($countryCode, $vendor['countries'], true);
			});

	}

	/**
	 * Inserts vendor into DB, returns new vendor ID
	 *
	 * @param array $vendor
	 *
	 * @return int|null
	 */
	private function insertVendor(array $vendor) {

		$sql = sprintf(
			"INSERT INTO `%s`
			(`carrier_id`, `carrier_name_cart`,`country`, `group`, `free_shipping_limit`, `max_weight`, `is_enabled`)
				VALUES (%s, '%s', '%s', '%s', %s, %s, %s)",
			DB_PREFIX . 'zasilkovna_vendor',
			$vendor['carrier_id'] ?: 'NULL',
			$this->db->escape($vendor['carrier_name_cart']),
			$this->db->escape($vendor['country']),
			$this->db->escape($vendor['group']),
			$vendor['free_shipping_limit'] ?: 'NULL',
			($vendor['max_weight'] === null) ? 'NULL' : $vendor['max_weight'],
			$vendor['is_enabled']);

		if ($this->db->query($sql)) {
			return $this->db->getLastId();
		}

		return null;
	}

	/**
	 * @param array $vendor
	 *
	 * @return int|array
	 */
	public function saveVendor(array $vendor) {
		if ($vendor['id'] === null) {
			return $this->insertVendor($vendor);
		}

		//TODO: update vendor
		return [];
	}

	/**
	 * @param array $vendorPrices
	 *
	 * @return array
	 */
	public function insertVendorPrices(array $vendorPrices) {
		$sql = sprintf(
			"INSERT INTO `%s` (`vendor_id`, `max_weight`, `price`)
			VALUES %s",
			DB_PREFIX . 'zasilkovna_vendor_price',
			implode(',', array_map(static function ($vendorPrice) {
				return sprintf(
					"(%s, %s, %s)",
					$vendorPrice['vendor_id'] ?: 'NULL',
					$vendorPrice['max_weight'] ?: 'NULL',
					$vendorPrice['price'] ?: 'NULL');
			}, $vendorPrices)));

		return $this->db->query($sql);
	}

	/**
	 * @param string $countryCode
	 *
	 * @return array
	 */
	public function getUsedVendorGroupsByCountry($countryCode) {
		$sql = sprintf('SELECT `group` FROM `%s` WHERE `country` = "%s" AND `carrier_id` IS NULL',
			DB_PREFIX . 'zasilkovna_vendor',
			$countryCode);

		$queryResult =$this->db->query($sql);
		$groups = [];
		if ($queryResult->num_rows === 0) {
			return $groups;
		}

		foreach($queryResult->rows as $row) {
			$groups[] = $row['group'];
		}

		return $groups;
	}

}
