<?php

namespace Packetery\Order;

use DB;

class OrderRepository
{
	const TABLE_NAME = DB_PREFIX . 'zasilkovna_orders';

	/** @var DB */
	private $db;

	public function __construct(DB $db)
	{
		$this->db = $db;
	}

	/**
	 * @return void
	 */
	public function insertIgnore(Order $order)
	{
		$orderId = (int)$order->getOrderId();
		$branchId = (int)$order->getBranchId();
		$escapedBranchName = $this->db->escape($order->getBranchName());
		$isCarrier = (int)$order->getIsCarrier();
		$escapedCarrierPickupPoint = $this->db->escape($order->getCarrierPickupPoint());
		$totalWeight = (float)$order->getTotalWeight();
		$carrierId = (int)$order->getCarrierId();
		$sql = "INSERT IGNORE INTO `" . self::TABLE_NAME . "` (`order_id`, `branch_id`, `branch_name`, `is_carrier`, `carrier_pickup_point`, `total_weight`, `carrier_id`) VALUES ({$orderId}, {$branchId}, \"{$escapedBranchName}\", {$isCarrier}, \"{$escapedCarrierPickupPoint}\", {$totalWeight}, {$carrierId});";
		$this->db->query($sql);
	}
}
