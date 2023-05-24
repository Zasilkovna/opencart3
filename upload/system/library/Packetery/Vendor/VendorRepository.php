<?php

namespace Packetery\Vendor;

use DB;

class VendorRepository {

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
	 *
	 * @return null|array
	 */
	public function getVendorsByCountry($country) {
		$countryCode = $this->db->escape($country);

		$query = sprintf(
			"SELECT `zv`.`carrier_id`, `zv`.`id` AS `vendor_id`, `zc`.`name`, `zv`.`carrier_name_cart`  
			FROM `%s` `zv` 
			LEFT JOIN `%s` `zc` ON `zv`.`carrier_id` = `zc`.`id`
			WHERE `zv`.`country` = '%s' OR `zc`.`country` = '%s'
			UNION
			SELECT `zc`.`id`, `zv`.`id` AS `vendor_id`, `zc`.`name`, `zv`.`carrier_name_cart`
			FROM `%s` `zc`
			LEFT JOIN `%s` `zv` ON `zv`.`carrier_id` = `zc`.`id`
			WHERE `zc`.`country` = '%s' AND `zc`.`deleted` = 0
			",
			DB_PREFIX . 'zasilkovna_vendor',
			DB_PREFIX . 'zasilkovna_carrier',
			$countryCode,
			$countryCode,
			DB_PREFIX . 'zasilkovna_carrier',
			DB_PREFIX . 'zasilkovna_vendor',
			$countryCode
		);

		$queryResult = $this->db->query($query);

		if ($queryResult->num_rows === 0) {
			return null;
		}

		return $queryResult->rows;
	}
}