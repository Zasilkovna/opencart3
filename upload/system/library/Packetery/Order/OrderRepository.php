<?php

namespace Packetery\Order;

use Packetery\Db\BaseRepository;

class OrderRepository extends BaseRepository
{
	const TABLE_PACKETA_ORDERS = DB_PREFIX . 'zasilkovna_orders';

	/**
	 * @param int $orderId
	 *
	 * @return array
	 */
	public function getOrder($orderId)
	{
		$sql = sprintf('
			SELECT `order_id`,
				`branch_id`,
				`branch_name`,
				`carrier_pickup_point`,
				`is_carrier`,
				`exported` 
			FROM `%s`
			WHERE `order_id` = %d',
			self::TABLE_PACKETA_ORDERS,
			$orderId
		);

		return $this->db->query($sql)->row;
	}

}

