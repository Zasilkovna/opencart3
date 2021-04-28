<?php
/**
 * Class with common definitions for Zasilkovna module
 */
class ZasilkovnaCommon extends Model {

	/** @var string identifier of message for missing user permission to modify setting of Zasilkovna */
	const ERROR_PERMISSION = 'error_permission';
	/** @var string identifier of message for missing required parameter */
	const ERROR_MISSING_PARAM = 'error_missing_param';
	/** @var string identifier of message for invalid weight */
	const ERROR_INVALID_WEIGHT = 'error_invalid_weight';
	/** @var string identifier of message for invalid price */
	const ERROR_INVALID_PRICE = 'error_invalid_price';
	/** @var string identifier of message for invalid weight range */
	const ERROR_INVALID_WEIGHT_RANGE = 'error_invalid_weight_range';
	/** @var string identifier of message for rules overlapping */
	const ERROR_RULES_OVERLAPPING = 'error_rules_overlapping';
	/** @var string identifier of message for duplicated rule for given country */
	const ERROR_DUPLICATE_COUNTRY_RULE = 'error_duplicate_country_rule';


}
