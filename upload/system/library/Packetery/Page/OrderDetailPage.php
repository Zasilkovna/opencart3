<?php

namespace Packetery\Page;

use Packetery\Exceptions\JsonException;
use Packetery\Order\OrderRepository;


class OrderDetailPage
{
	/** @var OrderRepository */
	private $orderRepository;

	/**
	 * @param OrderRepository $orderRepository
	 */
	public function __construct(OrderRepository $orderRepository)
	{
		$this->orderRepository = $orderRepository;
	}

	/**
	 * @param int $orderId
	 * @return array
	 */
	public function getOrderData($orderId)
	{
		return $this->orderRepository->getOrderById($orderId);
	}

	/**
	 * @return bool
	 * @throws JsonException
	 */
	public function save(array $postData)
	{
		if (! $this->isFormValid($postData)) {
			return false;
		}

		$targetPoint = json_decode(rawurldecode($postData['packeta-target-point']), false);
		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new JsonException();
		}

		if ($targetPoint->pickupPointType === 'external') {
			$data = [
				'branch_id'            => (int) $targetPoint->carrierId,
				'carrier_pickup_point' => (int) $targetPoint->carrierPickupPointId,
				'is_carrier'           => 1,
			];
		} else {
			$data = [
				'branch_id'            => (int) $targetPoint->id,
				'carrier_pickup_point' => '',
				'is_carrier'           => 0,
			];
		}

		$data['branch_name'] = $targetPoint->nameStreet;
		$orderId = (int) $postData['order_id'];

		return (bool) $this->orderRepository->updateById($orderId, $data);
	}

	/**
	 * @param array $postData
	 * @return bool
	 */
	private function isFormValid($postData)
	{
		return (isset($postData['order_id']) && is_numeric($postData['order_id'])
			&& isset($postData['packeta-target-point']) && !empty($postData['packeta-target-point'])
		);
	}

}
