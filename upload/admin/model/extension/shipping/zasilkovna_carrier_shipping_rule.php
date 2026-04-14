<?php

class ModelExtensionShippingZasilkovnaCarrierShippingRule extends Model
{
	const PRICE_DECIMAL_PRECISION = 2;

	/**
	 * @param int $carrierId
	 * @return array{
	 *   carrier_id: string,
	 *   is_enabled: string,
	 *   default_price: string,
	 *   free_shipping_limit: ?string
	 * }|array{}
	 */
	public function getRuleByCarrierId($carrierId)
	{
		$query = $this->db->query(
			'SELECT `carrier_id`, `is_enabled`, `default_price`, `free_shipping_limit`
			 FROM `' . DB_PREFIX . 'zasilkovna_carrier_shipping_rule`
			 WHERE `carrier_id` = ' . (int)$carrierId . '
			 LIMIT 1'
		);

		if (empty($query->rows)) {
			return [];
		}

		return $query->row;
	}

	/**
	 * @param int $carrierId
	 * @param array{
	 *   is_enabled: mixed,
	 *   default_price: mixed,
	 *   free_shipping_limit: mixed
	 * } $data
	 */
	public function saveRule($carrierId, array $data)
	{
		$defaultPrice = round(
			(float)str_replace(',', '.', (string)$data['default_price']),
			self::PRICE_DECIMAL_PRECISION
		);
		$freeShippingLimitRawValue = str_replace(',', '.', (string)$data['free_shipping_limit']);
		$freeShippingLimit = (
			$freeShippingLimitRawValue === ''
				? null
				: round((float)$freeShippingLimitRawValue, self::PRICE_DECIMAL_PRECISION)
		);

		$this->db->query(
			'REPLACE INTO `' . DB_PREFIX . 'zasilkovna_carrier_shipping_rule`
			(`carrier_id`, `is_enabled`, `default_price`, `free_shipping_limit`)
			VALUES (
				' . (int)$carrierId . ',
				' . (int)$data['is_enabled'] . ',
				' . $defaultPrice . ',
				' . ($freeShippingLimit === null ? 'NULL' : $freeShippingLimit) . '
			)'
		);
	}
}
