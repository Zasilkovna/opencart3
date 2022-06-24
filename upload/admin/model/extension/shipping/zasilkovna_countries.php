<?php

class ModelExtensionShippingZasilkovnaCountries extends Model {
	/**
	 * @property DB $db
	 */

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

}
