<?php

namespace Packetery\Order;

use Packetery\Db\BaseRepository;

class OrderRepository extends BaseRepository
{
    const TABLE_PACKETA_ORDERS = DB_PREFIX . 'zasilkovna_orders';
    const TABLE_ORDER = DB_PREFIX . 'order';
    const TABLE_COUNTRY = DB_PREFIX . 'country';

    /**
     * @param int $orderId
     *
     * @return array
     */
    public function getById($orderId)
    {
        $sql = sprintf(
            '
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
     * @return void
     */
    public function updateById($orderId, array $rawData) {
        $this->update(self::TABLE_PACKETA_ORDERS, $rawData, ['order_id' => $orderId]);
    }
}
