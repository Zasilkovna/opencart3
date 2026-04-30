<?php

declare(strict_types=1);

namespace Packetery\Carrier;

class CarrierRuleLimitsForForm
{
	/** @var array<int, array{value: string, price: string}> */
	private array $weightLimits;

	/** @var array<int, array{value: string, price: string}> */
	private array $totalPriceLimits;

	/**
	 * @param array<int, array{value: string, price: string}> $weightLimits
	 * @param array<int, array{value: string, price: string}> $totalPriceLimits
	 */
	public function __construct(array $weightLimits, array $totalPriceLimits)
	{
		$this->weightLimits = $weightLimits;
		$this->totalPriceLimits = $totalPriceLimits;
	}

	/**
	 * @return array<int, array{value: string, price: string}>
	 */
	public function getWeightLimits(): array
	{
		return $this->weightLimits;
	}

	/**
	 * @return array<int, array{value: string, price: string}>
	 */
	public function getTotalPriceLimits(): array
	{
		return $this->totalPriceLimits;
	}
}
