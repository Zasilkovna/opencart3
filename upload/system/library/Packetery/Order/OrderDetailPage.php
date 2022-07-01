<?php

namespace Packetery\Order;

use Packetery\Tools\Tools;
use Packetery\Db\BaseRepository;

class OrderDetailPage
{
	/** @var string internal ID of branch */
	const KEY_BRANCH_ID = 'packeta-branch-id';
	/** @var string descriptive name for save to additional order data */
	const KEY_BRANCH_NAME = 'packeta-branch-name';
	/** @var string carrier id of selected pickup point */
	const KEY_CARRIER_ID = 'packeta-carrier-id';
	/** @var string selected carrier pickup point */
	const KEY_CARRIER_PICKUP_POINT = 'packeta-carrier-pickup-point';
	const BRANCH_FORM_FIELDS  = [
		self::KEY_BRANCH_ID,
		self::KEY_BRANCH_NAME,
		self::KEY_CARRIER_ID,
		self::KEY_CARRIER_PICKUP_POINT,
	];

	/** @var OrderRepository */
	private $orderRepository;
	/** @var BaseRepository */
	private $baseRepository;
	/** @var \Session */
	private $session;
	/** @var \Request */
	private $request;

	/**
	 * @param OrderRepository $orderRepository
	 * @param \Session        $session
	 * @param \Request        $request
	 */
	public function __construct(OrderRepository $orderRepository, BaseRepository $baseRepository, \Session $session, \Request $request)
	{
		$this->orderRepository = $orderRepository;
		$this->baseRepository = $baseRepository;
		$this->session = $session;
		$this->request = $request;
	}

	/**
	 * @param int $orderId
	 *
	 * @return array
	 */
	public function getOrderData($orderId)
	{
		return $this->orderRepository->getOrderById($orderId);
	}

	/**
	 * @return bool
	 */

	public function save()
	{
		$postData = $this->request->post;
		if (Tools::issetAll($postData, array_merge(self::BRANCH_FORM_FIELDS, ['order_id']))) {
			if (empty($post[self::KEY_CARRIER_ID])) {
				$data = [
					'branch_id'            => (int) $postData[self::KEY_BRANCH_ID],
					'carrier_pickup_point' => null,
					'is_carrier'           => 0,
				];
			} else {
				$data = [
					'branch_id'            => (int) $postData[self::KEY_CARRIER_ID],
					'carrier_pickup_point' => $postData[self::KEY_CARRIER_PICKUP_POINT],
					'is_carrier'           => 1,
				];
			}
			$data['branch_name'] = $postData[self::KEY_BRANCH_NAME];
			$orderId = (int) $postData['order_id'];

			$sqlData = $this->baseRepository->generateSQLFromData($data);
			return (bool) $this->orderRepository->updateById($orderId, $sqlData);
		}

		return false;
	}

}
