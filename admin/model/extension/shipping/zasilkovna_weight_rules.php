<?php
require_once(__DIR__ . '/zasilkovna_common.php');

/**
 * Model for weight rules of extension for zasilkovna.
 *
 * @property DB $db
 * @property Loader $load
 * @property \Cart\User $user
 */
class ModelExtensionShippingZasilkovnaWeightRules extends ZasilkovnaCommon {

	/** @var string full name of DB table (including prefix) */
	const TABLE_NAME = DB_PREFIX . 'zasilkovna_weight_rules';

	/** @var string DB column - internal rule ID */
	const COLUMN_RULE_ID = 'rule_id';
	/** @var string DB column - ISO code of target country (2 characters) or word "other" */
	const COLUMN_TARGET_COUNTRY = 'target_country';
	/** @var string DB column - minimal weight (including) */
	const COLUMN_MIN_WEIGHT = 'min_weight';
	/** @var string DB column - maximal weight (except - lower than) */
	const COLUMN_MAX_WEIGHT = 'max_weight';
	/** @var string DB column - price for this weight range (>= min_weight, < max_weight) */
	const COLUMN_PRICE = 'price';

	/** @var string country code for "other" countries */
	const COUNTRY_OTHER = 'other';

	/**
	 * Returns list of rules for all countries.
	 * First level is country code. Second level is list of rules for country.
	 *
	 * @return array list of rules
	 */
	public function getAllRules() {
		$sqlQuery = sprintf('SELECT * FROM `%s` ORDER BY `target_country`, `min_weight`',self::TABLE_NAME);
		/** @var StdClass $queryResult */
		$queryResult = $this->db->query($sqlQuery);

		// conversion of flat list to two-dimensional array with rules for "other" countries as last item
		$result = [];
		$otherCountries = [];
		foreach ($queryResult->rows as $dbRow) {
			if (self::COUNTRY_OTHER === $dbRow[self::COLUMN_TARGET_COUNTRY]) {
				$otherCountries[] = $dbRow;
			}
			else {
				$result[$dbRow[self::COLUMN_TARGET_COUNTRY]]['items'][] = $dbRow;
			}
		}
		if (!empty($otherCountries)) {
			$result[self::COUNTRY_OTHER]['items'] = $otherCountries;
		}
		return $result;
	}

	/**
	 * Returns list of rules for given country.
	 *
	 * @param $countryCode string iso code of target country
	 * @return array list of rules
	 */
	public function getRulesForCountry($countryCode) {
		$sqlQuery = sprintf('SELECT * FROM `%s` WHERE `target_country` = "%s" ORDER BY `min_weight`;',
			self::TABLE_NAME, $this->db->escape($countryCode));

		/** @var StdClass $queryResult */
		$queryResult = $this->db->query($sqlQuery);
		return $queryResult->rows;
	}

	/**
	 * Get content of rule.
	 *
	 * @param $ruleId int internal rule ID
	 * @return array content of rule
	 */
	public function getRule($ruleId) {
		$sqlQuery = sprintf('SELECT * FROM %s WHERE `rule_id` = %s', self::TABLE_NAME, (int) $ruleId);
		/** @var StdClass $queryResult */
		$queryResult = $this->db->query($sqlQuery);

		return $queryResult->row;
	}

	/**
	 * Creates a new weight rule for country.
	 *
	 * @param array $ruleData data of new rule from POST parameters
	 * @param string $countryCode iso code of target country for new record
	 * @return string identifier of error message, empty if no error occurred
	 */
	public function addRule(array $ruleData, $countryCode) {
		$checkErrorMessage = $this->checkRuleData($ruleData, $countryCode);
		if (!empty($checkErrorMessage)) {
			return $checkErrorMessage;
		}

		$sqlQuery = sprintf('INSERT INTO `%s` (`target_country`, `min_weight`, `max_weight`, `price`) VALUES ("%s", %s, %s, %s);',
			self::TABLE_NAME, $this->db->escape($countryCode), (int) $ruleData[self::COLUMN_MIN_WEIGHT],
			(int) $ruleData[self::COLUMN_MAX_WEIGHT], (float) $ruleData[self::COLUMN_PRICE]);
		$this->db->query($sqlQuery);

		return '';
	}

	/**
	 * Edit an existing rule for country.
	 *
	 * @param int $ruleId internal ID of changed rule
	 * @param array $ruleData data of new rule from POST parameters
	 * @param string $countryCode iso code of target country for changed record
	 * @return string identifier of error message, empty if no error occurred
	 */
	public function editRule($ruleId, array $ruleData, $countryCode) {
		$ruleData[self::COLUMN_TARGET_COUNTRY] = $countryCode;
		$errorMessage = $this->checkRuleData($ruleData, $countryCode, $ruleId);
		if (!empty($errorMessage)) {
			return $errorMessage;
		}

		$sqlQuery = sprintf('UPDATE `%s` SET `min_weight`= %s, `max_weight` = %s, `price` = %s WHERE `rule_id` = %s',
			self::TABLE_NAME, (int) $ruleData[self::COLUMN_MIN_WEIGHT], (int) $ruleData[self::COLUMN_MAX_WEIGHT], (float) $ruleData[self::COLUMN_PRICE],
			(int) $ruleId);
		$this->db->query($sqlQuery);

		return '';
	}

	/**
	 * Check content of weight rule data.
	 *
	 * @param array $ruleData data of new rule from POST parameters
	 * @param string $countryCode iso code of target country for new record
	 * @param int $ruleToIgnore ID of ignored rule (for change of rule)
	 *
	 * @return string identifier of error message, empty if no error occurred
	 */
	public function checkRuleData(array $ruleData, $countryCode, $ruleToIgnore = 0) {
		// check if user has rights to modify setting
		if (!$this->user->hasPermission('modify', 'extension/shipping/zasilkovna')) {
			return self::ERROR_PERMISSION;
		}

		// check if weight and price is positive integer number including weight range
		$minWeight = (int) $ruleData[self::COLUMN_MIN_WEIGHT];
		$maxWeight = (int) $ruleData[self::COLUMN_MAX_WEIGHT];
		$price = (float) $ruleData[self::COLUMN_PRICE];

		if ($minWeight < 0 || $maxWeight <= 0) { // minimal weight can be 0
			return self::ERROR_INVALID_WEIGHT;
		}

		if ($minWeight >= $maxWeight) {
			return self::ERROR_INVALID_WEIGHT_RANGE;
		}

		if ($price <= 0) {
			return self::ERROR_INVALID_PRICE;
		}

		// check if rule is overlapping with any other rule
		$sqlQuery = sprintf('SELECT * FROM `%s` WHERE `min_weight`<%s AND `max_weight` > %s AND `target_country` = "%s"',
			self::TABLE_NAME, $maxWeight, $minWeight, $countryCode);
		if ($ruleToIgnore > 0) {
			$sqlQuery .= ' AND `rule_id` <> ' . $ruleToIgnore;
		}
		/** @var StdClass $sqlResult */
		$sqlResult = $this->db->query($sqlQuery);
		if ($sqlResult->num_rows > 0) {
			$errorMesage = sprintf(self::ERROR_RULES_OVERLAPPING, $sqlResult->row[self::COLUMN_MIN_WEIGHT],
				$sqlResult->row[self::COLUMN_MAX_WEIGHT]);
			return $errorMesage;
		}

		return ''; // no error
	}

	/**
	 * Delete an existing rules for country. All selected rules will be deleted.
	 *
	 * @param array $ruleIdList list of internal rule IDs
	 * @return string identifier of error message, empty if no error occurred
	 */
	public function deleteRules(array $ruleIdList) {
		// check if user has rights to modify setting
		if (!$this->user->hasPermission('modify', 'extension/shipping/zasilkovna')) {
			return self::ERROR_PERMISSION;
		}

		if (empty($ruleIdList)) { // check if list of rules to delete is not empty
			return '';
		}

		// convert array of rule IDs to list for sql command (including conversion to int)
		$sqlList = '';
		foreach ($ruleIdList as $ruleId) {
			$sqlList .= (int) $ruleId . ',';
		}
		$sqlList = substr($sqlList, 0, -1);

		$sqlQuery = sprintf('DELETE FROM %s WHERE `rule_id` IN (%s);', self::TABLE_NAME, $sqlList);
		$this->db->query($sqlQuery);

		return '';
	}
}
