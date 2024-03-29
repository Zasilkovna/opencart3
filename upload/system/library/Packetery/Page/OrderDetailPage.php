<?php

namespace Packetery\Page;

use Packetery\Order\OrderRepository;

class OrderDetailPage {

    /** @var OrderRepository */
    private $orderRepository;

    /**
     * @param OrderRepository $orderRepository
     */
    public function __construct(OrderRepository $orderRepository) {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param int $orderId
     * @return array
     */
    public function getOrderData($orderId) {
        return $this->orderRepository->getById($orderId);
    }

    /**
     * @param array $postData
     * @return bool
     */
    public function save(array $postData) {
        if (! $this->isFormValid($postData)) {
            return false;
        }

        $targetPoint = json_decode(rawurldecode($postData['packeta-target-point']), false);

        if ($targetPoint->pickupPointType === 'external') {
            $data = [
                'branch_id' => (int)$targetPoint->carrierId,
                'carrier_pickup_point' => (int)$targetPoint->carrierPickupPointId,
                'is_carrier' => 1,
            ];
        } else {
            $data = [
                'branch_id' => (int)$targetPoint->id,
                'carrier_pickup_point' => '',
                'is_carrier' => 0,
            ];
        }

        $data['branch_name'] = $targetPoint->nameStreet;
        $orderId = (int)$postData['order_id'];

        $this->orderRepository->updateById($orderId, $data);

        return true;
    }

    /**
     * @param array $postData
     * @return bool
     */
    private function isFormValid(array $postData) {
        return (isset($postData['order_id']) && is_numeric($postData['order_id'])
            && !empty($postData['packeta-target-point'])
        );
    }
}
