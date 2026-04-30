<?php

declare(strict_types=1);

namespace Packetery\Carrier;

class RateType
{
	public const DEFAULT_PRICE = 'default_price';
	public const WEIGHT_BASED = 'weight_based_rate';
	public const TOTAL_BASED = 'total_based_rate';

	private string $value;

	public function __construct(string $value)
	{
		if (!self::isValid($value)) {
			throw new \LogicException("Invalid rate type '{$value}'.");
		}

		$this->value = $value;
	}

	public static function getAllowedValues(): array
	{
		return [
			self::DEFAULT_PRICE,
			self::WEIGHT_BASED,
			self::TOTAL_BASED,
		];
	}

	public static function isValid(string $value): bool
	{
		return in_array($value, self::getAllowedValues(), true);
	}

	public function getValue(): string
	{
		return $this->value;
	}

	public function isLimitBased(): bool
	{
		return $this->value === self::WEIGHT_BASED || $this->value === self::TOTAL_BASED;
	}

}
