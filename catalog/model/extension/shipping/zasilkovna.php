<?php

/**
 * Class ModelExtensionShippingZasilkovna
 *
 * @property Config $config
 * @property DB $db
 * @property Loader $load
 * @property Language $language
 * @property Request $request
 * @property Session $session
 * @property \Cart\Cart $cart
 * @property \Cart\Currency $currency
 * @property \Cart\Tax $tax
 */
class ModelExtensionShippingZasilkovna extends Model {
	/** @var string identification of e-shop module version */
	const APP_IDENTITY = 'opencart-3.0-packeta-2.0.1';

	/** @var string internal ID of branch */
	const KEY_BRANCH_ID = 'zasilkovna_branch_id';
	/** @var string descriptive name for save to additional order data */
	const KEY_BRANCH_NAME = 'zasilkovna_branch_name';
	/** @var string descriptive name for display to customer */
	const KEY_BRANCH_DESCRIPTION = 'zasilkovna_branch_description';

	/** @var string name of table with content of geo zones */
	const TABLE_ZONE_TO_GEO_ZONE = DB_PREFIX . 'zone_to_geo_zone';
	/** @var string name of table with shipping rules for country */
	const TABLE_SHIPPING_RULES = DB_PREFIX . 'zasilkovna_shipping_rules';
	/** @var string name of table with content of geo zones */
	const TABLE_WEIGHT_RULES = DB_PREFIX . 'zasilkovna_weight_rules';

	/** @var string code used as iso code of "other countries" */
	const OTHER_COUNTRIES_CODE = 'other';

	/** @var string name of DB column for free shipping limit */
	const COLUMN_FREE_OVER_LIMIT = 'free_over_limit';
	/** @var string name of DB column for shipping price */
	const COLUMN_PRICE = 'price';
	/** @var string name of DB column for default shipping price */
	const COLUMN_DEFAULT_PRICE = 'default_price';

	/** @var int special value which means "unable to calculate price" */
	const PRICE_UNKNOWN = -1;
	/** @var string name of parameter for shipping price */
	const PARAM_PRICE = 'price';
	/** @var string name of parameter for service name */
	const PARAM_SERVICE_NAME = 'service_name';

	/** @var array list of supported countries */
	private $supportedCountries = ['cz', 'sk', 'pl', 'hu', 'ro'];

	/** @var array list of supported languages in widget */
	private $supportedLanguages = ['cs', 'sk', 'pl', 'hu', 'ro', 'en'];

	/**
	 * Check basic conditions if shipping through Zasilkovna is allowed.
	 *
	 * @param int $totalWeight total weight of order
	 * @param array $targetAddress target address for order
	 * @return boolean check result (TRUE = shipping allowed)
	 */
	private function checkBasicConditions($totalWeight, $targetAddress) {
		// check if module for Zasilkovna is enabled
		if (!(int)$this->config->get('shipping_zasilkovna_status')) {
			return false;
		}

		// check if total weight of order is lower than maximal allowed weight (if limit is defined)
		$maxWeight = (int)$this->config->get('shipping_zasilkovna_weight_max');
		if (!empty($maxWeight) && $totalWeight > $maxWeight) {
			return false;
		}

		// check if target address is from allowed country
		$targetCountry = strtolower($targetAddress['iso_code_2']);
		if (!in_array($targetCountry, $this->supportedCountries)) {
			return false;
		}

		// check if target customer address is in allowed geo zone (if zone limitation is defined)
		$configGeoZone = (int) $this->config->get('shipping_zasilkovna_geo_zone_id');
		if ($configGeoZone > 0) {
			// get country and zone from target address
			$cartCountry = $targetAddress['country_id'];
			$cartZone = $targetAddress['zone_id'];
			// check if given zone or whole country is part of geo zone from configuration
			$sqlQuery = sprintf('SELECT * FROM `%s` WHERE `geo_zone_id` = %s AND `country_id` = %s AND (`zone_id` = %s OR `zone_id` = 0)',
				self::TABLE_ZONE_TO_GEO_ZONE, $configGeoZone, $cartCountry, $cartZone);
			/** @var StdClass $queryResult */
			$queryResult = $this->db->query($sqlQuery);
			if (0 == $queryResult->num_rows) {
				return false;
			}
		}

		// all checks passed
		return true;
	}

	/**
	 * Calculation of shipping price. Returns price of shipping or -1 if price cannot be calculated.
	 *
	 * @param string $countryCode iso code of target country
	 * @param double $totalWeight total weight of order
	 * @param double $totalPrice total price of order
	 * @return array price of shipping and internal shipping service code
	 */
	private function calculatePrice($countryCode, $totalWeight, $totalPrice) {
		// get properties of shipping for target country
		$sqlQueryCountry = sprintf('SELECT * FROM `%s` WHERE `target_country` = "%s" AND `is_enabled` = 1;', self::TABLE_SHIPPING_RULES,
			$this->db->escape($countryCode));
		/** @var StdClass $sqlResult */
		$sqlResult = $this->db->query($sqlQueryCountry);
		if ($sqlResult->num_rows > 0) { // found record for target country
			$countryRow = $sqlResult->row;
			$countryExist = true;
		}
		else { // search for record for "other countries"
			$countryExist = false;
			$sqlQueryOtherCountries = sprintf('SELECT * FROM `%s` WHERE `target_country` = "%s" AND `is_enabled` = 1;', self::TABLE_SHIPPING_RULES,
				self::OTHER_COUNTRIES_CODE);
			/** @var StdClass $sqlResult */
			$sqlResult = $this->db->query($sqlQueryOtherCountries);
			if ($sqlResult->num_rows > 0) { // found record for "other countries"
				$countryRow = $sqlResult->row;
			}
		}

		if (isset($countryRow)) {
			if ($countryRow['' . self::COLUMN_FREE_OVER_LIMIT . ''] > 0 && $totalPrice > $countryRow[self::COLUMN_FREE_OVER_LIMIT]) {
				// price of order is over limit for free shipping
				return [
					self::PARAM_PRICE => 0,
					self::PARAM_SERVICE_NAME => ($countryExist ? $countryCode : self::OTHER_COUNTRIES_CODE)
				];
			}

			// search for weight rule for given country
			$sqlWeightRule = sprintf('SELECT * FROM `%s` WHERE `target_country` = "%s" AND `min_weight` <= %s AND `max_weight` > %s;',
				self::TABLE_WEIGHT_RULES, $countryExist ? $countryCode : self::OTHER_COUNTRIES_CODE, $totalWeight, $totalWeight);
			/** @var StdClass $sqlResult */
			$sqlResult = $this->db->query($sqlWeightRule);

			if ($sqlResult->num_rows > 0) { // found weight rule
				return [
					self::PARAM_PRICE => $sqlResult->row[self::COLUMN_PRICE],
					self::PARAM_SERVICE_NAME => ($countryExist ? $countryCode : self::OTHER_COUNTRIES_CODE)
				];
			}

			// check if default price for country is defined
			if ($countryRow[self::COLUMN_DEFAULT_PRICE] > 0) {
				return [
					self::PARAM_PRICE => $countryRow[self::COLUMN_DEFAULT_PRICE],
					self::PARAM_SERVICE_NAME => ($countryExist ? $countryCode : self::OTHER_COUNTRIES_CODE)
				];
			}
		}

		// check if price is over global limit for free shipping
		$globalFreeShippingLimit = (int)$this->config->get('shipping_zasilkovna_default_free_shipping_limit');
		if ($globalFreeShippingLimit > 0 && $totalPrice > $globalFreeShippingLimit) {
			return [
				self::PARAM_PRICE => 0,
				self::PARAM_SERVICE_NAME => 'any'
			];
		}

		// check if global price for shipping is defined
		$globalShippingPrice = (float)$this->config->get('shipping_zasilkovna_default_shipping_price');
		if ($globalShippingPrice > 0) {
			return [
				self::PARAM_PRICE => $globalShippingPrice,
				self::PARAM_SERVICE_NAME => 'any'
			];
		}

		// price cannot be calculated
		return [
			self::PARAM_PRICE => self::PRICE_UNKNOWN,
			self::PARAM_SERVICE_NAME => ''
		];
	}

	/**
	 * Returns parameters of available options for shipping.
	 * It is called from ControllerCheckoutShippingMethod for all registered shipping extensions.
	 *
	 * @param array $targetAddress
	 * @return array
	 */
	public function getQuote($targetAddress) {
		$this->load->language('extension/shipping/zasilkovna');
		$cartTotalWeight = $this->cart->getWeight();
		$cartCountryCode = strtolower($this->cart->session->data["shipping_address"]["iso_code_2"]);
		$cartTotalPrice = $this->cart->getTotal();

		// check base conditions for possibility to use "Zasilkovna" for shipping
		$checkResult = $this->checkBasicConditions($cartTotalWeight, $targetAddress);
		if (!$checkResult) {
			return  [];
		}

		// preparing inline code for include to html page
		// standalone files with static JS code and JS configuration data, must be included inline into description text of shipping item for zasilkovna
		// there is no way how to include it directly through controller using $this->document->addXXX()
		$inlineCode = $this->prepareCssCode() . $this->prepareJsCoonfigData($targetAddress) . $this->prepareJsCode();

		// calculate price of shipping (only one item can be displayed)
		$calcResult = $this->calculatePrice($cartCountryCode, $cartTotalWeight, $cartTotalPrice);
		$shippingPrice = $calcResult[self::PARAM_PRICE];
		$serviceCodeName = $calcResult[self::PARAM_SERVICE_NAME];
		if (self::PRICE_UNKNOWN == $shippingPrice) {
			return [];
		}

		// preparation of properties for shipping service definition
		$titleTextId = 'shipping_' . $serviceCodeName;
		$taxClassId = $this->config->get('shipping_zasilkovna_tax_class_id');

		// preparation of description text including inline Javascript and CSS code
		$taxValue = $this->tax->calculate($shippingPrice, $this->config->get('shipping_zasilkovna_tax_class_id'), $this->config->get('config_tax'));
		$descriptionText = $this->currency->format($taxValue, $this->session->data['currency'])
			. $inlineCode . '<span id="packeta-first-shipping-item"></span>';

		$quote_data[$serviceCodeName] = [
			'code' => 'zasilkovna.' . $serviceCodeName,
			'title' => $this->language->get($titleTextId),
			'cost' => $shippingPrice,
			'tax_class_id' => $taxClassId,
			'text' => $descriptionText
		];

		$method_data = [
			'code' => 'zasilkovna',
			'title' => $this->language->get('text_title'),
			'quote' => $quote_data,
			'sort_order' => $this->config->get('shipping_zasilkovna_sort_order'),
			'error' => false
		];

		return $method_data;
	}

	/**
	 * Returns content of required CSS file as inline code.
	 *
	 * @return string
	 */
	private function prepareCssCode() {
		$cssFileName = dirname(__FILE__, 4) . '/view/stylesheet/zasilkovna/zasilkovna.css';
		$cssPrefix = "<style type=\"text/css\">\n";
		$cssSuffix = "\n</style>\n";

		return $cssPrefix . file_get_contents($cssFileName) . $cssSuffix;
	}

	/**
	 * Returns content of required JS files as inline code.
	 *
	 * @return string
	 */
	private function prepareJsCode() {
		$jsFileDir = dirname(__FILE__, 4) . '/view/javascript/zasilkovna';
		$jsPrefix = "\n<script type=\"text/javascript\">\n";
		$jsSuffix = "\n</script>\n";

		// include code for load JS envelope of map widget directly from Zasilkovna
		$jsCode = '<script src="https://widget.packeta.com/www/js/library.js"></script>' . "\n";
		// include code for static js file
		$jsCode .= $jsPrefix . file_get_contents($jsFileDir . '/shippingExtension.js') . $jsSuffix;

		// include call of initialization method
		$jsCode .= $jsPrefix . 'window.setTimeout("zasilkovnaInitAll();",100);' . $jsSuffix;

		return $jsCode;
	}

	/**
	 * Returns configuration data for JS as inline code.
	 *
	 * @param array $address shipping address of customer
	 * @return string
	 */
	private function prepareJsCoonfigData($address) {
		$jsPrefix = "\n<script type=\"text/javascript\">\n"
				. "window.zasilkovnaWidgetParameters = {";
		$jsSuffix = "};\n</script>\n";

		// detect widget language and countries enabled for map widget
		$targetCountry = strtolower($address['iso_code_2']);
		$userLanguage = $this->language->get('code');
		if (!in_array($userLanguage, $this->supportedLanguages)) {
			$userLanguage = 'en';
		}

		$parameters = [
			'apiKey' => $this->config->get('shipping_zasilkovna_api_key'),
			'language' => $userLanguage,
			'enabledCountries' => $targetCountry,
			'customerAddress' => $address['address_1'] . ' ' . $address['address_2'] . $address['city'],
			'selectBranchText' => $this->language->get('choose_branch'),
			'noBranchSelectedText' => $this->language->get('no_branch_selected'),
			'appIdentity' => self::APP_IDENTITY
		];

		$jsCode = $jsPrefix;
		foreach ($parameters as $paramName => $paramValue) {
			$paramValue = str_replace('\\', '\\\\', $paramValue);
			$paramValue = str_replace('"', '\\"', $paramValue);
			$jsCode .= $paramName . ': "' . str_replace('\\', '\\\\', $paramValue) . '",';
		}
		$jsCode .= $jsSuffix;

		return $jsCode;
	}

	/**
	 * Loads properties of selected branch from session.
	 *
	 * @return array
	 */
	public function loadSelectedBranch() {
		$defaults = [
			self::KEY_BRANCH_ID => '',
			self::KEY_BRANCH_NAME => '',
			self::KEY_BRANCH_DESCRIPTION => ''
		];

		if (isset($this->session->data[self::KEY_BRANCH_ID])) {
			$defaults[self::KEY_BRANCH_ID] = $this->session->data[self::KEY_BRANCH_ID];
			$defaults[self::KEY_BRANCH_NAME] = $this->session->data[self::KEY_BRANCH_NAME];
			$defaults[self::KEY_BRANCH_DESCRIPTION] = $this->session->data[self::KEY_BRANCH_DESCRIPTION];
		}

		return $defaults;
	}

	/**
	 * Save properties of selected branch from session.
	 *
	 * @return void
	 */
	public function saveSelectedBranch() {
		if ($this->request->post[self::KEY_BRANCH_ID]) {
			$this->session->data[self::KEY_BRANCH_ID] = $this->request->post[self::KEY_BRANCH_ID];
			$this->session->data[self::KEY_BRANCH_NAME] = $this->request->post[self::KEY_BRANCH_NAME];
			$this->session->data[self::KEY_BRANCH_DESCRIPTION] = $this->request->post[self::KEY_BRANCH_DESCRIPTION];
		}
	}

	/**
	 * Save additional order data to DB during "order confirm".
	 * All required records with order data are created in DB during this step.
	 * This method is called by "after" event on catalog/controller/checkout/confirm.
	 *
	 * @return void
	 */
	public function saveOrderData() {
		// check if selected shipping method is stored in session, it should be saved in step 4 of checkout
		if (!isset($this->session->data['shipping_method']['code'])) {
			return;
		}

		// check if shipping name contains word "zasilkovna", format shlould be "zasilkovna.<titleOfMethod>"
		// title of shipping method for given country is set in settings of plugin
		$selectedShipping = $this->session->data['shipping_method']['code'];
		if (strpos($selectedShipping, 'zasilkovna') === false) {
			return;
		}

		// internal ID of order in e-shop
		$orderId = (int) $this->session->data['order_id'];
		// internal ID of selected target branch for pick-up
		$branchId = (int) $this->session->data[self::KEY_BRANCH_ID];
		// name of selected branch (provided by zasilkovna)
		$branchName = $this->session->data[self::KEY_BRANCH_NAME];
		// total weight of all products in cart (including product options which can modify product weight)
		$totalWeight = (double) $this->cart->getWeight();

		$sql = sprintf('INSERT INTO `%szasilkovna_orders` (`order_id`, `branch_id`, `branch_name`, `total_weight`) VALUES (%s, %s, "%s", %s);',
			DB_PREFIX, $orderId, $branchId, $this->db->escape($branchName), $totalWeight);
		$this->db->query($sql);
	}

	/**
	 * Clean-up of additional order data in session when order is finished.
	 * This method is called as "before" event on catalog/controller/checkout/success.
	 *
	 * @return void
	 */
	public function sessionCleanup() {
		// check if order is already completed
		// the same check is implemented in original method
		if (isset($this->session->data['order_id'])) {
			unset($this->session->data[self::KEY_BRANCH_ID]);
			unset($this->session->data[self::KEY_BRANCH_NAME]);
			unset($this->session->data[self::KEY_BRANCH_DESCRIPTION]);
		}
	}
}
