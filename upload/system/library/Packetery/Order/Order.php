<?php

namespace Packetery\Order;

class Order
{
	/** @var int */
	private $orderId;
	/** @var int */
	private $branchId;
	/** @var string */
	private $branchName;
	/** @var int */
	private $isCarrier;
	/** @var string */
	private $carrierPickupPoint;
	/** @var float */
	private $totalWeight;
	/** @var int */
	private $carrierId;

	/**
	 * @param int $orderId
	 * @param int $branchId
	 * @param string $branchName
	 * @param int $isCarrier
	 * @param string $carrierPickupPoint
	 * @param float $totalWeight
	 * @param int $carrierId
	 */
	public function __construct(
		$orderId,
		$branchId,
		$branchName,
		$isCarrier,
		$carrierPickupPoint,
		$totalWeight,
		$carrierId
	)
	{
		$this->orderId = (int)$orderId;
		$this->branchId = (int)$branchId;
		$this->branchName = (string)$branchName;
		$this->isCarrier = (int)$isCarrier;
		$this->carrierPickupPoint = (string)$carrierPickupPoint;
		$this->totalWeight = (float)$totalWeight;
		$this->carrierId = (int)$carrierId;
	}

	/**
	 * @return int
	 */
	public function getOrderId()
	{
		return $this->orderId;
	}

	/**
	 * @return int
	 */
	public function getBranchId()
	{
		return $this->branchId;
	}

	/**
	 * @return string
	 */
	public function getBranchName()
	{
		return $this->branchName;
	}

	/**
	 * @return int
	 */
	public function getIsCarrier()
	{
		return $this->isCarrier;
	}

	/**
	 * @return string
	 */
	public function getCarrierPickupPoint()
	{
		return $this->carrierPickupPoint;
	}

	/**
	 * @return float
	 */
	public function getTotalWeight()
	{
		return $this->totalWeight;
	}

	/**
	 * @return int
	 */
	public function getCarrierId()
	{
		return $this->carrierId;
	}
}
