<?php

namespace Packetery\DAL\Repository;

use Packetery\DAL\Entity\VendorPrice;
use Packetery\Db\BaseRepository;

class VendorPriceRepository extends BaseRepository {

	const TABLE_NAME = 'zasilkovna_vendor_price';

	/**
	 * @param int $vendorId
	 * @return array
	 */
	public function getByVendorId($vendorId) {
		return $this->db->queryResult('SELECT * FROM zasilkovna_vendor_price WHERE `vendor_id` = ? ORDER BY `max_weight` ASC', $vendorId)->fetchAll();
	}

	/**
	 * @param array $weightRules
	 * @param int $id
	 * @return void
	 */
	public function insertVendorPrices($id, array $weightRules) {
		$sql = sprintf(
			"INSERT INTO `%s` (`vendor_id`, `max_weight`, `price`) VALUES %s",
			DB_PREFIX . self::TABLE_NAME,
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
	 * @param VendorPrice $vendorPrice
	 * @return void
	 */
	public function save(VendorPrice $vendorPrice) {
		$priceId = $vendorPrice->getId();

		if ($priceId) {
			$sql = 'UPDATE vendor_prices SET vendor_id = ?, price = ? WHERE id = ?';
			$this->db->queryResult($sql, $vendorPrice->getVendorId(), $vendorPrice->getPrice(), $priceId);
		} else {
			$sql = 'INSERT INTO vendor_prices SET vendor_id = ?, price = ?';
			$this->db->queryResult($sql, $vendorPrice->getVendorId(), $vendorPrice->getPrice());
		}
	}
}
