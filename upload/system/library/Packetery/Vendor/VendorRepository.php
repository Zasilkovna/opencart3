<?php

namespace Packetery\Vendor;

use DB;

class VendorRepository {

	const INTERNAL_VENDORS = [
		['id' => 'zpoint', 'name' => 'Výdejní místa', 'country' => 'cz'],
		['id' => 'zbox', 'name' => 'Z-BOX', 'country' => 'cz'],
		['id' => 'alzabox', 'name' => 'Alzabox', 'country' => 'cz'],
		['id' => 'zpoint', 'name' => 'Výdejní místa', 'country' => 'sk'],
		['id' => 'zbox', 'name' => 'Z-BOX', 'country' => 'sk'],
		['id' => 'zpoint', 'name' => 'Výdejní místa', 'country' => 'hu'],
		['id' => 'zbox', 'name' => 'Z-BOX', 'country' => 'hu'],
		['id' => 'zpoint', 'name' => 'Výdejní místa', 'country' => 'ro'],
		['id' => 'zbox', 'name' => 'Z-BOX', 'country' => 'ro'],
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
	 * @param bool   $onlyEnabled
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
		return array_filter(self::INTERNAL_VENDORS,
			static function ($vendor) use ($countryCode) {
				return $vendor['country'] === $countryCode;
			});

	}

	/**
	 * Inserts vendor into DB, returns new vendor ID
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
	 * @return array
	 */
	private function updateVendor(array $vendor) {
		$sql = sprintf(
			"UPDATE `%s` 
			SET `carrier_id` = %s, 
				`carrier_name_cart` = '%s', 
				`country` = '%s', 
				`group` = '%s',
				`free_shipping_limit` = %s,
				`max_weight` = %s, 
				`is_enabled` = %s 
			WHERE `id` = %s",
			DB_PREFIX . 'zasilkovna_vendor',
			$vendor['carrier_id'] ?: 'NULL',
			$this->db->escape($vendor['carrier_name_cart']),
			$this->db->escape($vendor['country']),
			$this->db->escape($vendor['group']),
			$vendor['free_shipping_limit'] ?: 'NULL',
			$vendor['max_weight'] ?: 'NULL',
			$vendor['is_enabled'] ? 1 : 0,
			$vendor['id']);

		return $this->db->query($sql);
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

		return $this->updateVendor($vendor);
	}

	/**
	 * @param int[] $vendorPriceIds
	 *
	 * @return array
	 */
	public function deleteVendorPricesByIds(array $vendorPriceIds) {
		$sql = sprintf(
			"DELETE FROM `%s` WHERE `id` IN (%s)",
			DB_PREFIX . 'zasilkovna_vendor_price',
			implode(',', $vendorPriceIds));

		return $this->db->query($sql);
	}

	/**
	 * @param int $vendorId
	 *
	 * @return array|null
	 */
	public function getVendorPriceIdsByVendorId($vendorId) {
		$query = sprintf(
			"SELECT `id` FROM `%s` WHERE `vendor_id` = %s",
			DB_PREFIX . 'zasilkovna_vendor_price',
			$vendorId);

		$queryResult = $this->db->query($query);

		if ($queryResult->num_rows === 0) {
			return null;
		}

		return array_map(static function ($row) {
			return $row['id'];
		}, $queryResult->rows);
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
	 * Not used yet
	 *
	 * @param int $vendorId
	 * @param array $vendorPrices
	 *
	 * @return void
	 */
	public function updateVendorPricesByVendorId($vendorId, array $vendorPrices) {

		$idsToDelete = $this->getVendorPriceIdsByVendorId($vendorId);
		$insertQueryResult = $this->insertVendorPrices($vendorPrices);

		if ($idsToDelete !== null && count($idsToDelete) > 0) {
			$deleteQueryResult = ($this->deleteVendorPricesByIds($idsToDelete));
		}
	}

}
