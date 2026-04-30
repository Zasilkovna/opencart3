<?php

declare(strict_types=1);

namespace Packetery\Carrier;

use DB;
use StdClass;

class ShippingRuleRepository
{
	const PRICE_DECIMAL_PRECISION = 2;
	const TABLE_CARRIER_SHIPPING_RULE = 'zasilkovna_carrier_shipping_rule';
	const TABLE_CARRIER_SHIPPING_RULE_LIMIT = 'zasilkovna_carrier_shipping_rule_limit';

	private DB $db;

	public function __construct(DB $db)
	{
		$this->db = $db;
	}

	/**
	 * @param int[] $carrierIdRecords
	 * @return array<int, ShippingRule>
	 */
	public function getByCarrierIdRecords(array $carrierIdRecords): array
	{
		if ($carrierIdRecords === []) {
			return [];
		}

		$escapedCarrierIds = implode(',', array_map('intval', $carrierIdRecords));
		$table = DB_PREFIX . self::TABLE_CARRIER_SHIPPING_RULE;
		$sql = "SELECT `id`, `carrier_id`, `carrier_name`, `is_enabled`, `rate_type`, `default_price`, `free_shipping_limit`
			FROM `{$table}`
			WHERE `carrier_id` IN ({$escapedCarrierIds})";
		/** @var StdClass $queryResult */
		$queryResult = $this->db->query($sql);

		$shippingRules = [];
		$shippingRulesByRuleId = [];
		foreach ($queryResult->rows as $shippingRule) {
			$shippingRuleObject = new ShippingRule(
				(bool)$shippingRule['is_enabled'],
				($shippingRule['carrier_name'] === '' ? null : (string)$shippingRule['carrier_name']),
				new RateType($shippingRule['rate_type']),
				($shippingRule['default_price'] === null ? null : (float)$shippingRule['default_price']),
				($shippingRule['free_shipping_limit'] === null ? null : (float)$shippingRule['free_shipping_limit'])
			);
			$shippingRules[(int)$shippingRule['carrier_id']] = $shippingRuleObject;
			$shippingRulesByRuleId[(int)$shippingRule['id']] = $shippingRuleObject;
		}

		$this->populateLimitsByRuleIdRecords($shippingRulesByRuleId);

		return $shippingRules;
	}

	public function findByCarrierId(int $carrierId): ?ShippingRule
	{
		$carrierIdRecords = [(int)$carrierId];
		$shippingRulesByCarrierIdRecord = $this->getByCarrierIdRecords($carrierIdRecords);
		if (!isset($shippingRulesByCarrierIdRecord[(int)$carrierId])) {
			return null;
		}

		return $shippingRulesByCarrierIdRecord[(int)$carrierId];
	}

	/**
	 * @param array{
	 *   is_enabled: mixed,
	 *   carrier_name: mixed,
	 *   rate_type: mixed,
	 *   default_price: mixed,
	 *   free_shipping_limit: mixed,
	 *   weight_limits?: array,
	 *   total_price_limits?: array
	 * } $data
	 */
	public function saveByCarrierId(int $carrierId, array $data): void
	{
		$rateType = new RateType($data['rate_type']);
		$this->insertOrUpdateShippingRuleByCarrierId(
			$carrierId,
			(int)$data['is_enabled'],
			$this->normalizeNullableText($data['carrier_name']),
			$rateType,
			$this->normalizeNullableDecimal($data['default_price']),
			$this->normalizeNullableDecimal($data['free_shipping_limit'])
		);

		$this->saveRuleLimitsByCarrierId($carrierId, $rateType, $data);
	}

	public function insertOrUpdateShippingRuleByCarrierId(int $carrierId, int $isEnabled, ?string $carrierName, RateType $rateType, ?float $defaultPrice, ?float $freeShippingLimit): void
	{
		$escapedRateType = $this->db->escape($rateType->getValue());
		$carrierIdValue = $carrierId;
		$isEnabledValue = $isEnabled;
		$carrierNameValue = ($carrierName === null ? 'NULL' : "'" . $this->db->escape($carrierName) . "'");
		$defaultPriceValue = ($defaultPrice === null ? 'NULL' : $defaultPrice);
		$freeShippingLimitValue = ($freeShippingLimit === null ? 'NULL' : $freeShippingLimit);
		$table = DB_PREFIX . self::TABLE_CARRIER_SHIPPING_RULE;

		$this->db->query(
			"INSERT INTO `{$table}`
			(`carrier_id`, `carrier_name`, `is_enabled`, `rate_type`, `default_price`, `free_shipping_limit`)
			VALUES (
				{$carrierIdValue},
				{$carrierNameValue},
				{$isEnabledValue},
				'{$escapedRateType}',
				{$defaultPriceValue},
				{$freeShippingLimitValue}
			)
			ON DUPLICATE KEY UPDATE
				`carrier_name` = VALUES(`carrier_name`),
				`is_enabled` = VALUES(`is_enabled`),
				`rate_type` = VALUES(`rate_type`),
				`default_price` = VALUES(`default_price`),
				`free_shipping_limit` = VALUES(`free_shipping_limit`)"
		);
	}

	private function findRuleIdByCarrierId(int $carrierId): ?int
	{
		$carrierIdValue = $carrierId;
		$table = DB_PREFIX . self::TABLE_CARRIER_SHIPPING_RULE;
		$query = $this->db->query(
			"SELECT `id`
			 FROM `{$table}`
			 WHERE `carrier_id` = {$carrierIdValue}
			 LIMIT 1"
		);
		if ($query->rows === []) {
			return null;
		}

		return (int)$query->row['id'];
	}

	private function deleteLimitsByRuleId(int $carrierShippingRuleId): void
	{
		$carrierShippingRuleIdValue = $carrierShippingRuleId;
		$table = DB_PREFIX . self::TABLE_CARRIER_SHIPPING_RULE_LIMIT;
		$this->db->query(
			"DELETE FROM `{$table}`
			 WHERE `carrier_shipping_rule_id` = {$carrierShippingRuleIdValue}"
		);
	}

	private function insertLimit(int $carrierShippingRuleId, float $value, float $price): void
	{
		$carrierShippingRuleIdValue = $carrierShippingRuleId;
		$valueFloat = $value;
		$priceFloat = $price;
		$table = DB_PREFIX . self::TABLE_CARRIER_SHIPPING_RULE_LIMIT;
		$this->db->query(
			"INSERT INTO `{$table}`
			(`carrier_shipping_rule_id`, `value`, `price`)
			VALUES (
				{$carrierShippingRuleIdValue},
				{$valueFloat},
				{$priceFloat}
			)"
		);
	}

	/**
	 * @param array{
	 *   weight_limits?: array,
	 *   total_price_limits?: array
	 * } $data
	 */
	private function saveRuleLimitsByCarrierId(int $carrierId, RateType $rateType, array $data): void
	{
		$carrierShippingRuleId = $this->findRuleIdByCarrierId($carrierId);
		if ($carrierShippingRuleId === null) {
			return;
		}

		$this->deleteLimitsByRuleId($carrierShippingRuleId);
		if ($rateType->getValue() === RateType::DEFAULT_PRICE) {
			return;
		}

		$limits = [];
		if ($rateType->getValue() === RateType::WEIGHT_BASED && isset($data['weight_limits'])) {
			$limits = (array)$data['weight_limits'];
		}
		if ($rateType->getValue() === RateType::TOTAL_BASED && isset($data['total_price_limits'])) {
			$limits = (array)$data['total_price_limits'];
		}
		foreach ($limits as $limit) {
			if (!isset($limit['value'], $limit['price'])) {
				continue;
			}

			$limitValue = $this->normalizeNullableDecimal($limit['value']);
			$limitPrice = $this->normalizeNullableDecimal($limit['price']);
			if ($limitValue === null || $limitPrice === null) {
				continue;
			}

			$this->insertLimit($carrierShippingRuleId, $limitValue, $limitPrice);
		}
	}

	private function normalizeNullableDecimal(string|int|float|null $value): ?float
	{
		$normalizedValue = str_replace(',', '.', trim((string)$value));
		if ($normalizedValue === '') {
			return null;
		}

		return round((float)$normalizedValue, self::PRICE_DECIMAL_PRECISION);
	}

	private function normalizeNullableText(string|int|float|null $value): ?string
	{
		$normalizedValue = trim((string)$value);
		if ($normalizedValue === '') {
			return null;
		}

		return $normalizedValue;
	}

	/**
	 * @param array<int, ShippingRule> $shippingRulesByRuleId
	 */
	private function populateLimitsByRuleIdRecords(array $shippingRulesByRuleId): void
	{
		if ($shippingRulesByRuleId === []) {
			return;
		}

		$ruleIds = array_keys($shippingRulesByRuleId);
		$escapedRuleIds = implode(',', array_map('intval', $ruleIds));
		$limitsTable = DB_PREFIX . self::TABLE_CARRIER_SHIPPING_RULE_LIMIT;
		$sql = "SELECT `limit`.`carrier_shipping_rule_id`, `limit`.`value`, `limit`.`price`
			FROM `{$limitsTable}` `limit`
			WHERE `limit`.`carrier_shipping_rule_id` IN ({$escapedRuleIds})
			ORDER BY `limit`.`carrier_shipping_rule_id` ASC, `limit`.`value` ASC, `limit`.`id` ASC";
		/** @var StdClass $queryResult */
		$queryResult = $this->db->query($sql);

		foreach ($queryResult->rows as $limit) {
			$ruleId = (int)$limit['carrier_shipping_rule_id'];
			if (!isset($shippingRulesByRuleId[$ruleId])) {
				continue;
			}
			$shippingRule = $shippingRulesByRuleId[$ruleId];
			$shippingRuleLimit = new ShippingRuleLimit($shippingRule, (float)$limit['value'], (float)$limit['price']);
			$shippingRule->addLimit($shippingRuleLimit);
		}
	}
}
