<?php

namespace Packetery\Order;

use Packetery\Db\BaseRepository;

/**
 * @property \DB $db;
 */
class OrderRepository
{
	const TABLE_PACKETA_ORDERS = DB_PREFIX . 'zasilkovna_orders';
	const TABLE_ORDER = DB_PREFIX . 'order';
	const TABLE_COUNTRY = DB_PREFIX . 'country';

	/**
	 * @var \DB $db
	 */
	private $db;
	/**
	 * @var BaseRepository
	 */
	private $baseRepository;

	/**
	 * @param \DB            $db
	 * @param BaseRepository $baseRepository
	 */
	public function __construct(\DB $db, BaseRepository $baseRepository) {
		$this->db = $db;
		$this->baseRepository = $baseRepository;
	}

	/**
	 * @param int $orderId
	 *
	 * @return array
	 */
	public function getOrderById($orderId)
	{
		$sql = sprintf('
			SELECT `zo`.`order_id`,
				`zo`.`branch_id`,
				`zo`.`branch_name`,
				`zo`.`carrier_pickup_point`,
				`zo`.`is_carrier`,
				`zo`.`exported`,
				`c`.`iso_code_2` AS `shipping_country_code`
			FROM `%s` `zo`
			INNER JOIN `%s` `o` ON `zo`.`order_id` = `o`.`order_id`
			LEFT JOIN `%s` `c` ON `c`.`country_id` = `o`.`shipping_country_id`
			WHERE `zo`.`order_id` = %d',
			self::TABLE_PACKETA_ORDERS,
			self::TABLE_ORDER,
			self::TABLE_COUNTRY,
			$orderId
		);

		return $this->db->query($sql)->row;
	}

	/**
	 * @param int $orderId
	 * @param array $rawData
	 *
	 * @return array
	 */
	public function updateById($orderId, $rawData)
	{
		$sql = sprintf('
			UPDATE `%s` SET %s WHERE `order_id` = %d',
			self::TABLE_PACKETA_ORDERS,
			$this->baseRepository->generateSQLFromData($rawData),
			$orderId
		);

		return $this->db->query($sql);
	}

}

