<?php

namespace Packetery\Vendor;

use DB;
use Packetery\Db\BaseRepository;
use Packetery\Tools\Tools;

class VendorRepository extends BaseRepository {

	const PACKETA_VENDORS = [
		[
			'group' => 'zpoint',
			'name' => 'vendor_add_zpoint',
			'countries' => ['cz', 'sk', 'hu', 'ro',]
		],
		[
			'group' => 'zbox',
			'name' => 'vendor_add_zbox',
			'countries' => ['cz', 'sk', 'hu', 'ro',]
		],
		[
			'group' => 'alzabox',
			'name' => 'vendor_add_alzabox',
			'countries' => ['cz',]
		],
	];

	/**
	 * @param string $country
	 * @param bool $onlyEnabled
	 *
	 * @return array
	 */
	public function getVendorsByCountry($country, $onlyEnabled = false) {
		static $CACHE;

		if (isset($CACHE[$country])) {
			return $CACHE[$country];
		}

		$countryCode = $this->db->escape($country);

		$additionalWhere = '';
		if ($onlyEnabled) {
			$additionalWhere = ' AND `zv`.`is_enabled` = 1 ';
		}

		$query = sprintf(
			"SELECT `zv`.`carrier_id`,
				`zv`.`id` AS `vendor_id`,
				`zc`.`name`,
				`zv`.`cart_name`,
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
		$CACHE[$country] = $queryResult->rows;

		return $CACHE[$country];
	}

	/**
	 * @param string $countryCode
	 *
	 * @return array
	 */
	public function getPacketaVendorsByCountry($countryCode) {
		$result = [];

		foreach (self::PACKETA_VENDORS as $vendor) {
			if (in_array($countryCode, $vendor['countries'])) {
				$key = Tools::getUniquePacketaVendor($countryCode, $vendor['group']);
				unset($vendor['countries']);
				$result[$key] = $vendor;
			}
		}

		return $result;
	}

	/**
	 * @param string $group
	 * @return array
	 */
	public function getPacketaVendorByGroup($group) {
		foreach (self::PACKETA_VENDORS as $vendor) {
			if ($vendor['group'] === $group) {
				return $vendor;
			}
		}

		return [];
	}

	/**
	 * @param array $weightRules
	 * @param int $id
	 * @return void
	 */
	public function insertVendorPrices($id, array $weightRules) {
		$sql = sprintf(
			"INSERT INTO `%s` (`vendor_id`, `max_weight`, `price`) VALUES %s",
			DB_PREFIX . 'zasilkovna_vendor_price',
			implode(',', array_map(static function ($weightRule) use ($id) {
				return sprintf(
					"(%d, %f, %f)",
					$id,
					$weightRule['max_weight'],
					$weightRule['price']);
			}, $weightRules)));

		$this->db->query($sql);
	}

    /**
     * @param int $id
     *
     * @return array|null
     */
    public function getVendorById($id) {
        $sql = sprintf(
            "SELECT `zv`.`id`,
                `zv`.`carrier_id`,
                `zv`.`cart_name`,
                COALESCE(`zv`.`country`, `zc`.`country`) AS `country`,
                `zv`.`group`,
                `zv`.`free_shipping_limit`,
                `zv`.`is_enabled`
            FROM `%s` `zv` 
            LEFT JOIN `%s` `zc` 
                ON `zv`.`carrier_id` = `zc`.`id`
            WHERE `zv`.`id` = %d",
            DB_PREFIX . 'zasilkovna_vendor',
            DB_PREFIX . 'zasilkovna_carrier',
            $id
        );

        $queryResult = $this->db->query($sql);

        return !empty($queryResult->row) ? $queryResult->row : null;
    }
}
