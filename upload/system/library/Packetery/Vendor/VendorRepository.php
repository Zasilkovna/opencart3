<?php

namespace Packetery\Vendor;

use DB;

class VendorRepository {

	const COUNTRIES_WITH_PICKUP_POINTS = ['cz', 'sk', 'hu', 'ro'];

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
				IF('%s' IN ('%s'), 1, 0) AS 'has_pickup_points'
			FROM `%s` `zv` 
			LEFT JOIN `%s` `zc` ON `zv`.`carrier_id` = `zc`.`id`
			WHERE (`zv`.`country` = '%s' OR `zc`.`country` = '%s') %s
			UNION
			SELECT `zc`.`id`,
				`zv`.`id` AS `vendor_id`,
				`zc`.`name`,
				`zv`.`carrier_name_cart`,
				null AS `group`,
				`zc`.`country`,
				`zc`.`is_pickup_points` AS `has_pickup_points`
			FROM `%s` `zc`
			LEFT JOIN `%s` `zv` ON `zv`.`carrier_id` = `zc`.`id`
			WHERE `zc`.`country` = '%s' AND `zc`.`deleted` = 0
			",
			$countryCode,
			implode("','", self::COUNTRIES_WITH_PICKUP_POINTS),
			DB_PREFIX . 'zasilkovna_vendor',
			DB_PREFIX . 'zasilkovna_carrier',
			$countryCode,
			$countryCode,
			$additionalWhere,
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
