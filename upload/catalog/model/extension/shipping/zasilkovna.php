<?php

use Packetery\Carrier\Carrier;
use Packetery\Carrier\CarrierRepository;
use Packetery\Carrier\FinalCarrierNameResolver;
use Packetery\Carrier\RateType;
use Packetery\Carrier\ShippingRule;
use Packetery\Carrier\ShippingRuleRepository;
use Packetery\Order\Order;
use Packetery\Order\OrderRepository;
use Packetery\Widget\WidgetOptionsBuilder;

require_once DIR_SYSTEM . 'library/Packetery/autoload.php';

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
	/** @var string internal ID of country */
	const KEY_COUNTRY_ID = 'country_id';
	/** @var string internal ID of branch */
	const KEY_BRANCH_ID = 'zasilkovna_branch_id';
	/** @var string descriptive name for save to additional order data */
	const KEY_BRANCH_NAME = 'zasilkovna_branch_name';
	/** @var string widget carrier id of selected pickup point */
	const KEY_WIDGET_CARRIER_ID = 'zasilkovna_carrier_id';
	/** @var string selected carrier pickup point */
	const KEY_CARRIER_PICKUP_POINT = 'zasilkovna_carrier_pickup_point';
	/** @var string descriptive name for display to customer */
	const KEY_BRANCH_DESCRIPTION = 'zasilkovna_branch_description';

	/** @var string name of table with content of geo zones */
	const TABLE_ZONE_TO_GEO_ZONE = DB_PREFIX . 'zone_to_geo_zone';
	/** @var string name of table with carriers */
	const TABLE_CARRIERS = DB_PREFIX . 'zasilkovna_carrier';

	/** @var string name of parameter for shipping price */
	const PARAM_PRICE = 'price';

	/** @var array list of supported languages in widget */
	private $supportedLanguages = ['cs', 'sk', 'pl', 'hu', 'ro', 'en'];
	/** @var CarrierRepository */
	private $carrierRepository;
	/** @var ShippingRuleRepository */
	private $shippingRuleRepository;
	/** @var FinalCarrierNameResolver */
	private $finalCarrierNameResolver;
	/** @var OrderRepository */
	private $orderRepository;
	/** @var WidgetOptionsBuilder */
	private $widgetOptionsBuilder;

	public function __construct($registry)
	{
		parent::__construct($registry);
		$this->carrierRepository = new CarrierRepository($this->db);
		$this->shippingRuleRepository = new ShippingRuleRepository($this->db);
		$this->finalCarrierNameResolver = new FinalCarrierNameResolver();
		$this->orderRepository = new OrderRepository($this->db);
		$this->widgetOptionsBuilder = new WidgetOptionsBuilder();
	}

	/**
	 * Check basic conditions if shipping through Zasilkovna is allowed.
	 *
	 * @param float $totalWeight total weight of order
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
	 * @param string $countryCode
	 * @param float $totalWeight
	 * @return Carrier[]
	 */
	private function getAvailableCarriers($countryCode, $totalWeight) {
		$tableCarriers = self::TABLE_CARRIERS;
		$escapedCountryCode = $this->db->escape($countryCode);
		$totalWeight = (float)$totalWeight;
		$sql = "SELECT `c`.`id_record`, `c`.`id`, `c`.`name`, `c`.`country`, `c`.`currency`, `c`.`max_weight`, `c`.`is_pickup_points`,
			 `c`.`has_carrier_direct_label`, `c`.`customs_declarations`, `c`.`available`, `c`.`deleted`, `c`.`vendor_groups`
			FROM `{$tableCarriers}` `c`
			WHERE `c`.`available` = 1
				AND `c`.`deleted` = 0
				AND `c`.`country` = \"{$escapedCountryCode}\"
				AND `c`.`max_weight` >= {$totalWeight}
			ORDER BY `c`.`name` ASC";

		/** @var StdClass $queryResult */
		$queryResult = $this->db->query($sql);

		$carriers = [];
		foreach ($queryResult->rows as $carrierRow) {
			$carriers[] = new Carrier(
				(int)$carrierRow['id_record'],
				($carrierRow['id'] === null ? null : (int)$carrierRow['id']),
				(string)$carrierRow['name'],
				(string)$carrierRow['country'],
				(string)$carrierRow['currency'],
				(float)$carrierRow['max_weight'],
				(bool)$carrierRow['is_pickup_points'],
				(bool)$carrierRow['has_carrier_direct_label'],
				(bool)$carrierRow['customs_declarations'],
				(bool)$carrierRow['available'],
				(bool)$carrierRow['deleted'],
				($carrierRow['vendor_groups'] === null ? null : json_decode($carrierRow['vendor_groups'], true))
			);
		}

		return $carriers;
	}

	private function calculateRuleLimitsPrice(ShippingRule $shippingRule, RateType $rateType, float $cartTotalWeight, float $cartTotalProductPrice): ?float
	{
		$comparisonValue = 0.0;
		if ($rateType->getValue() === RateType::WEIGHT_BASED) {
			$comparisonValue = $cartTotalWeight;
		} else if ($rateType->getValue() === RateType::TOTAL_BASED) {
			$comparisonValue = $cartTotalProductPrice;
		}
		$comparisonValue = round((float)$comparisonValue, ShippingRuleRepository::PRICE_DECIMAL_PRECISION);

		foreach ($shippingRule->getLimits() as $ruleLimit) {
			$maxValue = round((float)$ruleLimit->getValue(), ShippingRuleRepository::PRICE_DECIMAL_PRECISION);
			if ($comparisonValue <= $maxValue) {
				return $ruleLimit->getPrice();
			}
		}

		return null;
	}

	private function calculateCarrierPrice(ShippingRule $shippingRule, float $cartTotalWeight, float $cartTotalProductPrice): ?float {
		$rateType = $shippingRule->getRateType();
		if ($rateType->isLimitBased()) {
			return $this->calculateRuleLimitsPrice($shippingRule, $rateType, (float)$cartTotalWeight, (float)$cartTotalProductPrice);
		}

		$carrierPrice = ($shippingRule->getDefaultPrice() === null ? 0.0 : $shippingRule->getDefaultPrice());
		$carrierFreeShippingLimit = $shippingRule->getFreeShippingLimit();

		if ($carrierFreeShippingLimit !== null && $carrierFreeShippingLimit > 0) {
			if ($cartTotalProductPrice >= $carrierFreeShippingLimit) {
				return 0.0;
			}

			if ($carrierPrice > 0) {
				return $carrierPrice;
			}

			$globalShippingPrice = (float)$this->config->get('shipping_zasilkovna_default_shipping_price');
			if ($globalShippingPrice > 0) {
				return $globalShippingPrice;
			}

			return null;
		}

		$globalFreeShippingLimit = (float)$this->config->get('shipping_zasilkovna_default_free_shipping_limit');
		if ($globalFreeShippingLimit > 0 && $cartTotalProductPrice >= $globalFreeShippingLimit) {
			return 0.0;
		}

		if ($carrierPrice > 0) {
			return $carrierPrice;
		}

		$globalShippingPrice = (float)$this->config->get('shipping_zasilkovna_default_shipping_price');
		if ($globalShippingPrice > 0) {
			return $globalShippingPrice;
		}

		return null;
	}

    /**
     * Method copied from \ModelLocalisationWeightClass because it changed signature/location multiple times while having same function.
     *
     * @param string $unit
     * @return array
     */
    public function getWeightClassDescriptionByUnit($unit) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "weight_class_description` WHERE `unit` = '" . $this->db->escape($unit) . "' AND `language_id` = '" . (int)$this->config->get('config_language_id') . "'");

        return $query->row;
    }

    /**
     * Gets cart weight in kilograms.
     *
     * @return float
     */
    private function getCartWeightKg()
    {
        $weightClassRow = $this->getWeightClassDescriptionByUnit('kg');
        return (float) $this->weight->convert($this->cart->getWeight(), $this->config->get('config_weight_class_id'), $weightClassRow['weight_class_id']);
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
		$cartTotalWeight = $this->getCartWeightKg();
		$cartTotalProductPrice = $this->cart->getSubTotal();
		$cartCountryCode = '';
		if (isset($targetAddress['iso_code_2'])) {
			$cartCountryCode = strtolower($targetAddress['iso_code_2']);
		} else if (isset($this->session->data['shipping_address']['iso_code_2'])) {
			$cartCountryCode = strtolower($this->session->data['shipping_address']['iso_code_2']);
		}
		if ($cartCountryCode === '') {
			return [];
		}

		// check base conditions for possibility to use "Zasilkovna" for shipping
		$checkResult = $this->checkBasicConditions($cartTotalWeight, $targetAddress);
		if (!$checkResult) {
			return  [];
		}

		$carriers = $this->getAvailableCarriers($cartCountryCode, $cartTotalWeight);
		if ($carriers === []) {
			return [];
		}
		$carrierIdRecords = [];
		foreach ($carriers as $carrier) {
			$carrierIdRecords[] = $carrier->getIdRecord();
		}
		$shippingRulesByCarrierIdRecord = $this->shippingRuleRepository->getByCarrierIdRecords($carrierIdRecords);

		$taxClassId = $this->config->get('shipping_zasilkovna_tax_class_id');
		$quote_data = [];
		foreach ($carriers as $carrier) {
			$carrierIdRecord = $carrier->getIdRecord();
			$shippingRule = new ShippingRule(null, null, new RateType(RateType::DEFAULT_PRICE), null, null);
			if (isset($shippingRulesByCarrierIdRecord[$carrierIdRecord])) {
				$shippingRule = $shippingRulesByCarrierIdRecord[$carrierIdRecord];
			}

			if ($shippingRule->getIsEnabled() !== null && $shippingRule->getIsEnabled() === false) {
				continue;
			}

			$shippingPrice = $this->calculateCarrierPrice($shippingRule, $cartTotalWeight, $cartTotalProductPrice);
			if ($shippingPrice === null) {
				continue;
			}

			$taxValue = $this->tax->calculate($shippingPrice, $taxClassId, $this->config->get('config_tax'));
			$descriptionText = $this->currency->format($taxValue, $this->session->data['currency']);
			if ($carrier->isPickupPoints()) {
				$jsConfigData = $this->getJsConfig($carrier, $targetAddress);
				$descriptionText .= '<span class="packeta-shipping-item-config" data-method-code="zasilkovna.' . $carrierIdRecord . '"'
					. ' data-select-branch-text="' . htmlspecialchars($this->language->get('choose_branch')) . '"'
					. ' data-no-branch-selected-text="' . htmlspecialchars($this->language->get('no_branch_selected')) . '"'
					. $jsConfigData . '></span>';
			}

			$carrierName = $this->finalCarrierNameResolver->resolve($carrier, $shippingRule);
			$quote_data[$carrierIdRecord] = [
				'code' => 'zasilkovna.' . $carrierIdRecord,
				'title' => $carrierName,
				'cost' => $shippingPrice,
				'tax_class_id' => $taxClassId,
				'text' => $descriptionText
			];
		}
		if ($quote_data === []) {
			return [];
		}

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
		$cssFileName = DIR_APPLICATION . 'view/theme/zasilkovna/zasilkovna.css';
        $cssPrefix = "<style type=\"text/css\">\n";
        $cssSuffix = "\n</style>\n";

        return $cssPrefix . file_get_contents($cssFileName) . $cssSuffix;
	}

    /** identification of e-shop module version
     * @return string
     */
    private static function getAppIdentity()
    {
        require_once DIR_APPLICATION . '../admin/controller/extension/shipping/zasilkovna.php';
        return 'opencart-3.0-packeta-' . \ControllerExtensionShippingZasilkovna::VERSION;
    }

	/**
	 * @param array $address
	 * @return string
	 */
	private function getJsConfig(Carrier $carrier, $address)
	{
		$userLanguage = $this->language->get('code');
		if (!in_array($userLanguage, $this->supportedLanguages)) {
			$userLanguage = 'en';
		}

		$parameters = [
			'api_key' => $this->config->get('shipping_zasilkovna_api_key'),
			'customer_address' => $address['address_1'] . ' ' . $address['address_2'] . $address['city'],
			'select_branch_text' => $this->language->get('choose_branch'),
			'no_branch_selected_text' => $this->language->get('no_branch_selected'),
		];
		$widgetOptions = $this->widgetOptionsBuilder->createPickupPointForCheckout($carrier, $userLanguage, self::getAppIdentity());
		$parameters['language'] = $widgetOptions['language'];
		$parameters['enabled_countries'] = $widgetOptions['country'];
		$parameters['app_identity'] = $widgetOptions['appIdentity'];
		if (isset($widgetOptions['vendors'])) {
			$parameters['vendors'] = json_encode($widgetOptions['vendors']);
		}

		$output = '';
		foreach ($parameters as $param => $value) {
			$output .= sprintf(' data-%s="%s"', $param, htmlspecialchars($value));
		}

		return $output;
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
			self::KEY_BRANCH_DESCRIPTION => '',
			self::KEY_WIDGET_CARRIER_ID => '',
			self::KEY_CARRIER_PICKUP_POINT => '',
		];

		if (isset($this->session->data[self::KEY_BRANCH_ID])) {
			$defaults[self::KEY_BRANCH_ID] = $this->session->data[self::KEY_BRANCH_ID];
			$defaults[self::KEY_BRANCH_NAME] = $this->session->data[self::KEY_BRANCH_NAME];
			$defaults[self::KEY_BRANCH_DESCRIPTION] = $this->session->data[self::KEY_BRANCH_DESCRIPTION];
		}
		if (isset($this->session->data[self::KEY_WIDGET_CARRIER_ID])) {
			$defaults[self::KEY_WIDGET_CARRIER_ID] = $this->session->data[self::KEY_WIDGET_CARRIER_ID];
		}
		if (isset($this->session->data[self::KEY_CARRIER_PICKUP_POINT])) {
			$defaults[self::KEY_CARRIER_PICKUP_POINT] = $this->session->data[self::KEY_CARRIER_PICKUP_POINT];
		}

		return $defaults;
	}

	/**
	 * Save properties of selected branch from session.
	 *
	 * @return void
	 */
	public function saveSelectedBranch() {
        $this->session->data[self::KEY_BRANCH_ID] = $this->request->post[self::KEY_BRANCH_ID];
        $this->session->data[self::KEY_BRANCH_NAME] = $this->request->post[self::KEY_BRANCH_NAME];
        $this->session->data[self::KEY_BRANCH_DESCRIPTION] = $this->request->post[self::KEY_BRANCH_DESCRIPTION];
        $this->session->data[self::KEY_WIDGET_CARRIER_ID] = $this->request->post[self::KEY_WIDGET_CARRIER_ID];
        $this->session->data[self::KEY_CARRIER_PICKUP_POINT] = $this->request->post[self::KEY_CARRIER_PICKUP_POINT];
	}

	public function saveSelectedCountry($cartType)
	{
		// add new cart here 2/3
		switch ($cartType) {
			case 'standard':
				$countryId = $this->request->post[self::KEY_COUNTRY_ID];
				break;
			case 'journal3':
				$countryId = $this->request->post['order_data']['shipping_country_id'];
				break;
		}
		if ($countryId) {
			$this->session->data[self::KEY_COUNTRY_ID] = $countryId;
		}
	}

	/**
	 * Save additional order data to DB during "order confirm" of journal3.
	 *
	 * @return void
	 */
	public function journal3SaveOrderData() {
		$isJournal3Confirm = isset($this->request->get['confirm']) && $this->request->get['confirm'] === 'true';
		if (!$isJournal3Confirm) {
			return;
		}

		$this->saveOrderData();
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

		$shippingMethodParts = explode('.', $selectedShipping);
		$carrierRecordId = 0;
		if (isset($shippingMethodParts[1])) {
			$carrierRecordId = (int)$shippingMethodParts[1];
		}
		$selectedCarrier = null;
		if ($carrierRecordId > 0) {
			$selectedCarrier = $this->carrierRepository->findByIdRecord($carrierRecordId);
		}

		// internal ID of order in e-shop
		$orderId = (int) $this->session->data['order_id'];

		// TODO: optimize?
		// this check is needed because the method is being called by checkout/save/after trigger of OPC journal3
		// and not only once by checkout/confirm/after as usual
		if (!$orderId) {
			return;
		}
		$isCarrier = 0;
		$branchId = 0;
		$branchName = '';
		$carrierPickupPoint = '';
		if ($selectedCarrier !== null && $selectedCarrier->isPickupPoints()) {
			if (isset($this->session->data[self::KEY_BRANCH_ID])) {
				$branchId = (int)$this->session->data[self::KEY_BRANCH_ID];
			}
			if (isset($this->session->data[self::KEY_WIDGET_CARRIER_ID]) && $this->session->data[self::KEY_WIDGET_CARRIER_ID] !== '') {
				$isCarrier = 1;
			}
			if (isset($this->session->data[self::KEY_BRANCH_NAME])) {
				$branchName = $this->session->data[self::KEY_BRANCH_NAME];
			}
			if (isset($this->session->data[self::KEY_CARRIER_PICKUP_POINT])) {
				$carrierPickupPoint = $this->session->data[self::KEY_CARRIER_PICKUP_POINT];
			}
		} else if ($selectedCarrier !== null) {
			$branchId = ($selectedCarrier->getId() === null ? $carrierRecordId : (int)$selectedCarrier->getId());
			$branchName = $selectedCarrier->getName();
			if ($branchId > 0) {
				$isCarrier = 1;
			}
		}
		$totalWeight = $this->getCartWeightKg();
		$this->orderRepository->insertIgnore(new Order($orderId, $branchId, $branchName, $isCarrier, $carrierPickupPoint, $totalWeight, $carrierRecordId));
	}

	public function sessionCleanup() {
		// check if order is already completed
		// the same check is implemented in original method
		if (isset($this->session->data['order_id'])) {
			unset($this->session->data[self::KEY_BRANCH_ID]);
			unset($this->session->data[self::KEY_BRANCH_NAME]);
			unset($this->session->data[self::KEY_BRANCH_DESCRIPTION]);
			unset($this->session->data[self::KEY_WIDGET_CARRIER_ID]);
			unset($this->session->data[self::KEY_CARRIER_PICKUP_POINT]);
		}
	}
}
