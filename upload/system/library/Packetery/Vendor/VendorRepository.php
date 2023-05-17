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
}
