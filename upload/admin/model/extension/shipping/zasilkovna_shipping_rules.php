<?php
require_once(__DIR__ . '/zasilkovna_common.php');

/**
 * Model for shipping rules of extension for zasilkovna.
 *
 * @property DB $db
 * @property Loader $load
 * @property \Cart\User $user
 */
class ModelExtensionShippingZasilkovnaShippingRules extends ZasilkovnaCommon {

    /** @var string full name of DB table (including prefix) */
    const TABLE_NAME = DB_PREFIX . 'zasilkovna_shipping_rules';

    /** @var string DB column - internal rule ID */
    const COLUMN_RULE_ID = 'rule_id';
    /** @var string DB column - ISO code of target country (2 characters) or word "other" */
    const COLUMN_TARGET_COUNTRY = 'target_country';
    /** @var string DB column - default shipping price if price from weight rule is not used */
    const COLUMN_DEFAULT_PRICE = 'default_price';
    /** @var string DB column - price limit for free shipping */
    const COLUMN_FREE_OVER_LIMIT = 'free_over_limit';
    /** @var string DB column - flag if rule is enabled */
    const COLUMN_IS_ENABLED = 'is_enabled';

    /**
     * Returns list of shipping rules.
     *
     * @return array list of rules
     */
    public function getAllRules() {
        $sqlQuery = sprintf('SELECT * FROM `%s` ORDER BY `rule_id`',self::TABLE_NAME);
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
     * @return string identifier of error message, empty if no error occurred
     */
    public function addRule(array $ruleData) {
        $checkErrorMessage = $this->checkRuleData($ruleData);
        if (!empty($checkErrorMessage)) {
            return $checkErrorMessage;
        }

        $sqlQuery = sprintf('INSERT INTO `%s` (`target_country`, `default_price`, `free_over_limit`, `is_enabled`) VALUES ("%s", "%.2f","%.2f", %s);',
            self::TABLE_NAME, $this->db->escape($ruleData[self::COLUMN_TARGET_COUNTRY]), (float) $ruleData[self::COLUMN_DEFAULT_PRICE],
            (float) $ruleData[self::COLUMN_FREE_OVER_LIMIT], (int) $ruleData[self::COLUMN_IS_ENABLED]);
        $this->db->query($sqlQuery);

        return '';
    }

    /**
     * Edit an existing rule for country.
     *
     * @param int $ruleId internal ID of changed rule
     * @param array $ruleData data of new rule from POST parameters
     * @return string identifier of error message, empty if no error occurred
     */
    public function editRule($ruleId, array $ruleData) {
        $errorMessage = $this->checkRuleData($ruleData, $ruleId);
        if (!empty($errorMessage)) {
            return $errorMessage;
        }

        $sqlQuery = sprintf('UPDATE `%s` SET `target_country`= "%s", `default_price` = "%.2f", `free_over_limit` = "%.2f", `is_enabled` = %s WHERE `rule_id` = %s',
            self::TABLE_NAME, $this->db->escape($ruleData[self::COLUMN_TARGET_COUNTRY]), (float) $ruleData[self::COLUMN_DEFAULT_PRICE],
            (float) $ruleData[self::COLUMN_FREE_OVER_LIMIT], (int) $ruleData[self::COLUMN_IS_ENABLED], (int) $ruleId);
        $this->db->query($sqlQuery);

        return '';
    }

    /**
     * Check content of weight rule data.
     *
     * @param array $ruleData data of new rule from POST parameters
     * @param int $ruleToIgnore ID of ignored rule (for change of rule)
     *
     * @return string identifier of error message, empty if no error occurred
     */
    public function checkRuleData(array $ruleData, $ruleToIgnore = 0) {
        // check if user has rights to modify setting
        if (!$this->user->hasPermission('modify', 'extension/shipping/zasilkovna')) {
            return self::ERROR_PERMISSION;
        }

        // check if defined price is valid positive integer number
        // both items are optional and can be empty
        if (!empty($ruleData[self::COLUMN_DEFAULT_PRICE])) {
            $defaultPrice = (int) $ruleData[self::COLUMN_DEFAULT_PRICE];
            if ($defaultPrice <= 0) {
                return self::ERROR_INVALID_PRICE;
            }
        }
        // check if defined free over limit is valid non-negative integer number
        if (!empty($ruleData[self::COLUMN_FREE_OVER_LIMIT])) {
            $freeOverLimit = (int) $ruleData[self::COLUMN_FREE_OVER_LIMIT];
            if ($freeOverLimit < 0) {
                return self::ERROR_INVALID_PRICE;
            }
        }

        $country = $ruleData[self::COLUMN_TARGET_COUNTRY];

        // check if rule is rule for this country is already defined
        $sqlQuery = sprintf('SELECT * FROM `%s` WHERE `target_country`="%s"',
            self::TABLE_NAME, $this->db->escape($country));
        if ($ruleToIgnore > 0) {
            $sqlQuery .= ' AND `rule_id` <> ' . $ruleToIgnore;
        }
        /** @var StdClass $sqlResult */
        $sqlResult = $this->db->query($sqlQuery);
        if ($sqlResult->num_rows > 0) {
            return self::ERROR_DUPLICATE_COUNTRY_RULE;
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
