<?php

declare(strict_types=1);

namespace Packetery\Carrier;

class ShippingRuleLimit
{
	private ShippingRule $shippingRule;
	private float $value;
	private float $price;

	public function __construct(ShippingRule $shippingRule, float $value, float $price)
	{
		$this->shippingRule = $shippingRule;
		$this->value = $value;
		$this->price = $price;
	}

	public function getShippingRule(): ShippingRule
	{
		return $this->shippingRule;
	}

	public function getValue(): float
	{
		return $this->value;
	}

	public function getPrice(): float
	{
		return $this->price;
	}
}
