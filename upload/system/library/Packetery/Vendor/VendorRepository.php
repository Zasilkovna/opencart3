<?php

namespace Packetery\Vendor;

use DB;

class VendorRepository {

	const INTERNAL_VENDORS = [
		['id' => 'zpoint', 'name' => 'Výdejní místa', 'country' => 'cz'],
		['id' => 'zbox', 'name' => 'Z-BOX', 'country' => 'cz'],
		['id' => 'alzabox','name' => 'Alzabox','country' => 'cz'],
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
	 * @param $country
	 *
	 * @return array
	 */
	public function getInternalVendorsByCountry($country) {
		return array_filter(self::INTERNAL_VENDORS,
			static function ($vendor) use ($country) {
				return $vendor['country'] === $country;
		});

	}


	public function saveVendorWeightPrices($vendorId, $weightPrice) {

		$this->db->query(
			sprintf(
				"INSERT INTO `%s` (`vendor_id`, `weight_price`) VALUES ('%s', '%s') ON DUPLICATE KEY UPDATE `weight_price` = '%s'",
				DB_PREFIX . 'zasilkovna_vendor_price',
				$this->db->escape($vendorId),
				$this->db->escape($weightPrice),
				$this->db->escape($weightPrice)
			)
		);
	}
}
