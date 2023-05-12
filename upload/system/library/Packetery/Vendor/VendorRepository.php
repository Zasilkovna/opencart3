<?php

namespace Packetery\Vendor;

use DB;

class VendorRepository {

	/** @var DB */
	private $db;

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
			"SELECT `zv`.`carrier_id`, `zc`.`name`, `zv`.`carrier_name_cart`  
			FROM `%s` `zv` 
			LEFT JOIN `%s` `zc` ON `zv`.`carrier_id` = `zc`.`id`
			WHERE `zv`.`country` = '%s'
			UNION
			SELECT `zc`.`id`, `zc`.`name`, null 
			FROM `%s` `zc` 
			WHERE `zc`.`country` = '%s' AND `zc`.`deleted` = 0
			",
			DB_PREFIX . 'zasilkovna_vendor',
			DB_PREFIX . 'zasilkovna_carrier',
			$countryCode,
			DB_PREFIX . 'zasilkovna_carrier',
			$countryCode
		);

		$queryResult = $this->db->query($query);

		if ($queryResult->num_rows === 0) {
			return null;
		}

		return $queryResult->rows;
	}
}
