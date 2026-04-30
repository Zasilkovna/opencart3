<?php

declare(strict_types=1);

namespace Packetery\Carrier;

class ShippingRule
{
	private ?bool $isEnabled;
	private RateType $rateType;
	private ?float $defaultPrice;
	private ?float $freeShippingLimit;
	private ?string $carrierName;

	/** @var ShippingRuleLimit[] */
	private array $limits = [];

	public function __construct(?bool $isEnabled, ?string $carrierName, RateType $rateType, ?float $defaultPrice, ?float $freeShippingLimit)
	{
		$this->isEnabled = $isEnabled;
		$this->carrierName = $carrierName;
		$this->rateType = $rateType;
		$this->defaultPrice = $defaultPrice;
		$this->freeShippingLimit = $freeShippingLimit;
	}

	public function getIsEnabled(): ?bool
	{
		return $this->isEnabled;
	}

	public function getRateType(): RateType
	{
		return $this->rateType;
	}

	public function getCarrierName(): ?string
	{
		return $this->carrierName;
	}

	public function getDefaultPrice(): ?float
	{
		return $this->defaultPrice;
	}

	public function getFreeShippingLimit(): ?float
	{
		return $this->freeShippingLimit;
	}

	public function addLimit(ShippingRuleLimit $limit): void
	{
		$this->limits[] = $limit;
	}

	/**
	 * @return ShippingRuleLimit[]
	 */
	public function getLimits(): array
	{
		return $this->limits;
	}
}
