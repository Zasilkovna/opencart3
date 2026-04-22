<?php

declare(strict_types=1);

namespace Packetery\Carrier;

class CarrierRuleFormService
{
	public function formatPriceForForm(?float $price): string
	{
		if ($price === null) {
			return '';
		}

		return number_format(
			$price,
			ShippingRuleRepository::PRICE_DECIMAL_PRECISION,
			'.',
			''
		);
	}

	/**
	 * @param ShippingRuleLimit[] $ruleLimits
	 */
	public function formatCarrierRuleLimitsForForm(RateType $rateType, array $ruleLimits): CarrierRuleLimitsForForm
	{
		$weightLimits = [];
		$totalPriceLimits = [];

		foreach ($ruleLimits as $ruleLimit) {
			if ($rateType->getValue() === RateType::WEIGHT_BASED) {
				$weightLimits[] = [
					'value' => $this->formatPriceForForm($ruleLimit->getValue()),
					'price' => $this->formatPriceForForm($ruleLimit->getPrice()),
				];
			}
			if ($rateType->getValue() === RateType::TOTAL_BASED) {
				$totalPriceLimits[] = [
					'value' => $this->formatPriceForForm($ruleLimit->getValue()),
					'price' => $this->formatPriceForForm($ruleLimit->getPrice()),
				];
			}
		}

		return new CarrierRuleLimitsForForm($weightLimits, $totalPriceLimits);
	}

	/**
	 * @param array<int, array{value?: mixed, price?: mixed}> $ruleLimits
	 * @return array<int, array{value: string, price: string}>
	 */
	public function ensureAtLeastOneCarrierRuleLimitForForm(RateType $selectedRateType, RateType $ruleType, array $ruleLimits): array
	{
		if ($selectedRateType->getValue() !== $ruleType->getValue()) {
			return $ruleLimits;
		}
		if ($ruleLimits !== []) {
			return $ruleLimits;
		}

		return [['value' => '', 'price' => '']];
	}

	/**
	 * @param array<int, array{value?: mixed, price?: mixed}> $weightLimits
	 * @param array<int, array{value?: mixed, price?: mixed}> $totalPriceLimits
	 */
	public function validateCarrierRuleLimits(RateType $rateType, array $weightLimits, array $totalPriceLimits): CarrierRuleLimitsValidationReport
	{
		if (!$this->shouldRequireCarrierRuleLimits($rateType)) {
			return CarrierRuleLimitsValidationReport::valid();
		}

		$ruleLimits = [];
		if ($rateType->getValue() === RateType::WEIGHT_BASED) {
			$ruleLimits = $weightLimits;
		}
		if ($rateType->getValue() === RateType::TOTAL_BASED) {
			$ruleLimits = $totalPriceLimits;
		}
		if ($ruleLimits === []) {
			return CarrierRuleLimitsValidationReport::invalid('error_sr_rule_required');
		}

		foreach ($ruleLimits as $ruleLimit) {
			$value = '';
			if (isset($ruleLimit['value'])) {
				$value = trim((string)$ruleLimit['value']);
			}
			$price = '';
			if (isset($ruleLimit['price'])) {
				$price = trim((string)$ruleLimit['price']);
			}
			if ($value === '' || $price === '') {
				return CarrierRuleLimitsValidationReport::invalid('error_sr_rule_required');
			}
			if (!is_numeric(str_replace(',', '.', $value)) || !is_numeric(str_replace(',', '.', $price))) {
				return CarrierRuleLimitsValidationReport::invalid('error_sr_rule_invalid_number');
			}
		}

		return CarrierRuleLimitsValidationReport::valid();
	}

	private function shouldRequireCarrierRuleLimits(RateType $rateType): bool
	{
		return $rateType->isLimitBased();
	}
}
