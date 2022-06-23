<?php

class ModelExtensionShippingZasilkovnaCountries extends Model {
	/**
	 * @property DB $db
	 */

	const PACKETA_PP_COUNTRY_ISO2_CODES = [
		'CZ',
		'SK',
		'HU',
		'RO',
		];
	/**
	 * @param $code
	 *
	 * @return string|null
	 */
	public function getCountryNameByIsoCode2($code) {
		if (empty($code)) {
			return null;
		}

		$country = $this->getCountryByIsoCode2(strtoupper((string)$code));
		if ($country) {
			return $country['name'];
		}

		return $code;
	}

	/**
	 * @param string $code
	 *
	 * @return array|null
	 */
	public function getCountryByIsoCode2($code) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE iso_code_2 = '" . $this->db->escape($code) . "'");

		if (empty($query)) {
			return null;
		}

		return $query->row;
	}

	/**
	 * @param array $countryIsos
	 */
	public function getCountriesByIso2($countryIsos) {

		$sql = sprintf('SELECT `name`, `iso_code_2` FROM `%s` WHERE `iso_code_2` IN (%s) ORDER BY `name`',
			DB_PREFIX . 'country',
		' \'' . implode('\',\'', $countryIsos) . '\' '
		);

		return $this->db->query($sql);
	}

	public function getPacketaCountries() {
		return $this->getCountriesByIso2(self::PACKETA_PP_COUNTRY_ISO2_CODES);
	}

}
